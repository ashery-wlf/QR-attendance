/**
 * Free location picker: Leaflet + OpenStreetMap tiles + Photon search (no API key).
 */
(function (global) {
    const DEFAULT_CENTER = { lat: -6.7924, lng: 39.2083 };
    const PHOTON_SEARCH = "https://photon.komoot.io/api/";
    const PHOTON_REVERSE = "https://photon.komoot.io/reverse";

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

    function formatPhotonAddress(properties) {
        if (!properties) {
            return "";
        }
        return [
            properties.name,
            properties.housenumber,
            properties.street,
            properties.city || properties.locality,
            properties.state,
            properties.country
        ].filter(Boolean).join(", ");
    }

    function photonSearch(query) {
        const url = PHOTON_SEARCH
            + "?q=" + encodeURIComponent(query)
            + "&limit=1&lang=en"
            + "&lat=" + DEFAULT_CENTER.lat
            + "&lon=" + DEFAULT_CENTER.lng;
        return fetch(url).then(function (response) {
            return response.json();
        });
    }

    function photonReverse(lat, lng) {
        const url = PHOTON_REVERSE + "?lat=" + lat + "&lon=" + lng + "&lang=en";
        return fetch(url).then(function (response) {
            return response.json();
        });
    }

    const LocationPicker = {
        queue: [],
        displays: [],
        instances: {},

        registerPicker: function (config) {
            this.queue.push(config);
            if (typeof global.L !== "undefined") {
                initPicker(config);
            }
        },

        registerDisplay: function (config) {
            this.displays.push(config);
            if (typeof global.L !== "undefined") {
                initDisplay(config);
            }
        },

        initAll: function () {
            if (typeof global.L === "undefined") {
                return;
            }
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

        if (mapEl._locationPickerMap) {
            return;
        }

        const defaults = normalizeCoords(cfg.defaultLat ?? DEFAULT_CENTER.lat, cfg.defaultLng ?? DEFAULT_CENTER.lng);
        let start = defaults;
        if (latInput.value !== "" && lngInput.value !== "") {
            start = normalizeCoords(latInput.value, lngInput.value);
        }

        const hasLocation = latInput.value !== "" && lngInput.value !== "";
        const draggable = cfg.draggable !== false;

        const map = L.map(mapEl).setView([start.lat, start.lng], hasLocation ? 16 : 6);
        mapEl._locationPickerMap = map;

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: "&copy; OpenStreetMap contributors"
        }).addTo(map);

        let marker = null;

        function applyPhotonProperties(properties) {
            const address = formatPhotonAddress(properties);
            if (addressInput && address) {
                addressInput.value = address;
            }
            if (cityInput && properties) {
                const city = properties.city || properties.locality || properties.county || "";
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

            if (marker) {
                marker.setLatLng([coords.lat, coords.lng]);
            } else {
                marker = L.marker([coords.lat, coords.lng], { draggable: draggable }).addTo(map);
                if (draggable) {
                    marker.on("dragend", function (event) {
                        const pos = event.target.getLatLng();
                        setPosition(pos.lat, pos.lng, null, { reverseGeocode: true });
                        setStatus(statusEl, "Mahali limesasishwa kwenye ramani.");
                    });
                }
            }

            if (zoom) {
                map.setView([coords.lat, coords.lng], zoom);
            } else {
                map.panTo([coords.lat, coords.lng]);
            }

            if (options.address && addressInput) {
                addressInput.value = options.address;
            } else if (options.reverseGeocode !== false && cfg.reverseGeocode !== false) {
                photonReverse(coords.lat, coords.lng)
                    .then(function (data) {
                        if (data.features && data.features[0]) {
                            applyPhotonProperties(data.features[0].properties);
                        }
                    })
                    .catch(function () {
                        /* ignore */
                    });
            }

            if (typeof cfg.onPositionChange === "function") {
                cfg.onPositionChange(coords.lat, coords.lng, options);
            }

            setTimeout(function () {
                map.invalidateSize();
            }, 100);
        }

        const instanceKey = mapEl.id || ("picker_" + Object.keys(LocationPicker.instances).length);
        LocationPicker.instances[instanceKey] = { setPosition: setPosition, map: map };

        if (draggable) {
            map.on("click", function (event) {
                setPosition(event.latlng.lat, event.latlng.lng, 16, { reverseGeocode: true });
                setStatus(statusEl, "Mahali limechaguliwa kwenye ramani.");
            });
        }

        function runSearch() {
            const query = (searchInput ? searchInput.value : "").trim();
            if (!query) {
                setStatus(statusEl, "Andika jina la mahali kwanza.");
                return;
            }
            setStatus(statusEl, "Inatafuta (huduma bure)...");
            photonSearch(query)
                .then(function (data) {
                    if (!data.features || !data.features.length) {
                        setStatus(statusEl, "Mahali halijapatikana. Jaribu jina lingine.");
                        return;
                    }
                    const feature = data.features[0];
                    const lon = feature.geometry.coordinates[0];
                    const lat = feature.geometry.coordinates[1];
                    setPosition(lat, lon, 16, {
                        address: formatPhotonAddress(feature.properties),
                        reverseGeocode: false
                    });
                    setStatus(statusEl, "Mahali limepatikana.");
                })
                .catch(function () {
                    setStatus(statusEl, "Imeshindwa kutafuta. Angalia intaneti.");
                });
        }

        if (findButton) {
            findButton.addEventListener("click", runSearch);
        }

        if (searchInput) {
            searchInput.addEventListener("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    runSearch();
                }
            });
        }

        if (currentButton) {
            currentButton.addEventListener("click", function () {
                if (!navigator.geolocation) {
                    setStatus(statusEl, "Browser haitambui GPS.");
                    return;
                }
                setStatus(statusEl, "Inachukua eneo lako...");
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        setPosition(position.coords.latitude, position.coords.longitude, 16, { reverseGeocode: true });
                        setStatus(statusEl, "Mahali limechaguliwa kwenye ramani.");
                    },
                    function (error) {
                        // Fallback to default Tanzania location if geolocation denied
                        setPosition(-6.7924, 39.2083, 14, { reverseGeocode: true });
                        setStatus(statusEl, "Mahali limechaguliwa kwenye ramani. (Default Tanzania location)");
                    },
                    { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
                );
            });
        }

        if (hasLocation) {
            setPosition(start.lat, start.lng, 16, { reverseGeocode: false });
        }
    }

    function initDisplay(cfg) {
        const mapEl = resolveElement(cfg.mapElement);
        if (!mapEl || mapEl._locationPickerMap) {
            return;
        }

        const normalized = normalizeCoords(cfg.lat, cfg.lng);
        const lat = normalized.lat;
        const lng = normalized.lng;

        const map = L.map(mapEl).setView([lat, lng], cfg.zoom || 16);
        mapEl._locationPickerMap = map;

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: "&copy; OpenStreetMap contributors"
        }).addTo(map);

        let marker = L.marker([lat, lng]).addTo(map);

        const instanceKey = mapEl.id || ("display_" + Object.keys(LocationPicker.instances).length);
        LocationPicker.instances[instanceKey] = {
            setPosition: function (newLat, newLng, zoom, statusText) {
                const coords = normalizeCoords(newLat, newLng);
                if (Number.isNaN(coords.lat) || Number.isNaN(coords.lng)) {
                    return;
                }
                marker.setLatLng([coords.lat, coords.lng]);
                map.setView([coords.lat, coords.lng], zoom || map.getZoom());
                if (statusText && cfg.statusElement) {
                    setStatus(resolveElement(cfg.statusElement), statusText);
                }
                setTimeout(function () {
                    map.invalidateSize();
                }, 100);
            },
            map: map
        };
    }

    global.LocationPicker = LocationPicker;
    global.GoogleMapsPicker = LocationPicker;

    document.addEventListener("DOMContentLoaded", function () {
        LocationPicker.initAll();
    });
})(window);
