(function (global) {
    const DEFAULT_CENTER = { lat: -6.7924, lng: 39.2083 };

    function resolveElement(ref) {
        if (!ref) {
            return null;
        }
        if (typeof ref === "string") {
            return document.getElementById(ref) || document.querySelector(ref);
        }
        return ref;
    }

    function setStatus(el, text) {
        if (el) {
            el.textContent = text;
        }
    }

    function pickCity(components) {
        if (!components) {
            return "";
        }
        const order = ["locality", "administrative_area_level_2", "administrative_area_level_1"];
        for (let i = 0; i < order.length; i += 1) {
            const part = components.find(function (item) {
                return item.types.indexOf(order[i]) !== -1;
            });
            if (part) {
                return part.long_name;
            }
        }
        return "";
    }

    function normalizeCoords(lat, lng) {
        if (global.appNormalizeLatLng) {
            return global.appNormalizeLatLng(lat, lng);
        }
        return { lat: parseFloat(lat), lng: parseFloat(lng) };
    }

    function writeCoords(latInput, lngInput, latDisplay, lngDisplay, lat, lng) {
        const coords = normalizeCoords(lat, lng);
        if (Number.isNaN(coords.lat) || Number.isNaN(coords.lng)) {
            return null;
        }

        const latFixed = Number(coords.lat.toFixed(7));
        const lngFixed = Number(coords.lng.toFixed(7));

        if (latInput) {
            latInput.value = latFixed;
        }
        if (lngInput) {
            lngInput.value = lngFixed;
        }
        if (latDisplay) {
            latDisplay.value = latFixed;
        }
        if (lngDisplay) {
            lngDisplay.value = lngFixed;
        }

        return { lat: latFixed, lng: lngFixed };
    }

    const GoogleMapsPicker = {
        queue: [],
        displays: [],
        instances: {},
        ready: false,

        registerPicker: function (config) {
            this.queue.push(config);
            if (this.ready) {
                initPicker(config);
            }
        },

        registerDisplay: function (config) {
            this.displays.push(config);
            if (this.ready) {
                initDisplay(config);
            }
        },

        initAll: function () {
            if (!global.google || !global.google.maps) {
                return;
            }
            this.ready = true;
            this.queue.forEach(initPicker);
            this.displays.forEach(initDisplay);
        }
    };

    function initPicker(cfg) {
        const mapEl = resolveElement(cfg.mapElement);
        const latInput = resolveElement(cfg.latInput);
        const lngInput = resolveElement(cfg.lngInput);
        const latDisplay = resolveElement(cfg.latDisplay);
        const lngDisplay = resolveElement(cfg.lngDisplay);
        const searchInput = resolveElement(cfg.searchInput);
        const findButton = resolveElement(cfg.findButton);
        const currentButton = resolveElement(cfg.currentButton);
        const addressInput = resolveElement(cfg.addressInput);
        const cityInput = resolveElement(cfg.cityInput);
        const statusEl = resolveElement(cfg.statusElement);

        if (!mapEl || !latInput || !lngInput) {
            return;
        }

        const defaults = normalizeCoords(cfg.defaultLat ?? DEFAULT_CENTER.lat, cfg.defaultLng ?? DEFAULT_CENTER.lng);
        let start = defaults;

        if (latInput.value !== "" && lngInput.value !== "") {
            start = normalizeCoords(latInput.value, lngInput.value);
        }

        const hasLocation = latInput.value !== "" && lngInput.value !== "";
        const map = new google.maps.Map(mapEl, {
            center: { lat: start.lat, lng: start.lng },
            zoom: hasLocation ? 16 : 6,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true
        });

        let marker = null;
        const geocoder = new google.maps.Geocoder();
        const draggable = cfg.draggable !== false;
        const reverseGeocode = cfg.reverseGeocode !== false;

        function applyAddressFromGeocode(results) {
            if (!results || !results[0]) {
                return;
            }
            const result = results[0];
            if (addressInput) {
                addressInput.value = result.formatted_address || "";
            }
            if (cityInput && result.address_components) {
                const city = pickCity(result.address_components);
                if (city) {
                    cityInput.value = city;
                }
            }
        }

        function setPosition(lat, lng, zoom, options) {
            options = options || {};
            const coords = writeCoords(latInput, lngInput, latDisplay, lngDisplay, lat, lng);
            if (!coords) {
                return;
            }

            const pos = { lat: coords.lat, lng: coords.lng };

            if (marker) {
                marker.setPosition(pos);
            } else {
                marker = new google.maps.Marker({
                    map: map,
                    position: pos,
                    draggable: draggable
                });
                if (draggable) {
                    marker.addListener("dragend", function () {
                        const point = marker.getPosition();
                        setPosition(point.lat(), point.lng(), null, { reverseGeocode: true });
                        setStatus(statusEl, "Location updated on Google Maps.");
                    });
                }
            }

            map.panTo(pos);
            if (zoom) {
                map.setZoom(zoom);
            }

            if (options.address && addressInput) {
                addressInput.value = options.address;
            } else if (options.reverseGeocode && reverseGeocode) {
                geocoder.geocode({ location: pos }, function (results, status) {
                    if (status === "OK") {
                        applyAddressFromGeocode(results);
                    }
                });
            }

            if (typeof cfg.onPositionChange === "function") {
                cfg.onPositionChange(coords.lat, coords.lng, options);
            }
        }

        const instanceKey = mapEl.id || ("picker_" + Object.keys(GoogleMapsPicker.instances).length);
        GoogleMapsPicker.instances[instanceKey] = {
            setPosition: setPosition,
            map: map
        };

        if (draggable) {
            map.addListener("click", function (event) {
                setPosition(event.latLng.lat(), event.latLng.lng(), 16, { reverseGeocode: true });
                setStatus(statusEl, "Location selected on Google Maps.");
            });
        }

        if (searchInput && google.maps.places) {
            const autocomplete = new google.maps.places.Autocomplete(searchInput, {
                fields: ["geometry", "formatted_address", "name", "address_components"]
            });
            if (cfg.countryRestriction) {
                autocomplete.setComponentRestrictions({ country: cfg.countryRestriction });
            }
            autocomplete.addListener("place_changed", function () {
                const place = autocomplete.getPlace();
                if (!place.geometry || !place.geometry.location) {
                    setStatus(statusEl, "Choose location ya Google.");
                    return;
                }
                setPosition(
                    place.geometry.location.lat(),
                    place.geometry.location.lng(),
                    16,
                    {
                        address: place.formatted_address || place.name || "",
                        reverseGeocode: false
                    }
                );
                setStatus(statusEl, "Location found on Google Maps.");
            });
        }

        if (findButton) {
            findButton.addEventListener("click", function () {
                const query = (searchInput ? searchInput.value : "").trim();
                if (!query) {
                    setStatus(statusEl, "Write name of location.");
                    return;
                }
                setStatus(statusEl, "Search on Google Maps...");
                geocoder.geocode({ address: query }, function (results, status) {
                    if (status !== "OK" || !results[0]) {
                        setStatus(statusEl, "Location not found on Google Maps.");
                        return;
                    }
                    const loc = results[0].geometry.location;
                    setPosition(loc.lat(), loc.lng(), 16, {
                        address: results[0].formatted_address,
                        reverseGeocode: false
                    });
                    setStatus(statusEl, "Location found on Google Maps.");
                });
            });
        }

        if (currentButton) {
            currentButton.addEventListener("click", function () {
                if (!navigator.geolocation) {
                    setStatus(statusEl, "Browser dosn't know GPS.");
                    return;
                }
                setStatus(statusEl, "Its Takes your location...");
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        setPosition(position.coords.latitude, position.coords.longitude, 16, { reverseGeocode: true });
                        setStatus(statusEl, "Location saved.");
                    },
                    function () {
                        setStatus(statusEl, "Isn't take your location.");
                    },
                    { enableHighAccuracy: true, timeout: 12000 }
                );
            });
        }

        if (hasLocation) {
            setPosition(start.lat, start.lng, 16, { reverseGeocode: false });
        }
    }

    function initDisplay(cfg) {
        const mapEl = resolveElement(cfg.mapElement);
        if (!mapEl) {
            return;
        }

        let lat = cfg.lat;
        let lng = cfg.lng;
        const normalized = normalizeCoords(lat, lng);
        lat = normalized.lat;
        lng = normalized.lng;

        const map = new google.maps.Map(mapEl, {
            center: { lat: lat, lng: lng },
            zoom: cfg.zoom || 16,
            mapTypeControl: false,
            streetViewControl: false,
            zoomControl: true,
            draggable: cfg.interactive !== false,
            scrollwheel: cfg.interactive !== false,
            disableDoubleClickZoom: cfg.interactive === false
        });

        let marker = new google.maps.Marker({
            map: map,
            position: { lat: lat, lng: lng }
        });

        const instanceKey = mapEl.id || ("display_" + Object.keys(GoogleMapsPicker.instances).length);
        GoogleMapsPicker.instances[instanceKey] = {
            setPosition: function (newLat, newLng, zoom, addressText) {
                const coords = normalizeCoords(newLat, newLng);
                if (Number.isNaN(coords.lat) || Number.isNaN(coords.lng)) {
                    return;
                }
                const pos = { lat: coords.lat, lng: coords.lng };
                marker.setPosition(pos);
                map.panTo(pos);
                if (zoom) {
                    map.setZoom(zoom);
                }
                if (addressText && cfg.statusElement) {
                    setStatus(resolveElement(cfg.statusElement), addressText);
                }
            },
            map: map
        };
    }

    global.GoogleMapsPicker = GoogleMapsPicker;
    global.__initGoogleMapsApi = function () {
        GoogleMapsPicker.initAll();
    };
})(window);
