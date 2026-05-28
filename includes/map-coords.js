/**
 * Normalize latitude/longitude when values were saved in the wrong order.
 * Tanzania example: correct is lat ~ -7, lng ~ 37 (not lat 37, lng -7).
 */
function appNormalizeLatLng(lat, lng) {
    lat = parseFloat(lat);
    lng = parseFloat(lng);

    if (Number.isNaN(lat) || Number.isNaN(lng)) {
        return { lat: NaN, lng: NaN };
    }

    if (lat === 0 && lng === 0) {
        return { lat: 0, lng: 0 };
    }

    if (Math.abs(lat) > 90 && Math.abs(lng) <= 90) {
        return { lat: lng, lng: lat };
    }

    if (Math.abs(lng) > 180 && Math.abs(lat) <= 90) {
        return { lat: lng, lng: lat };
    }

    // Common swap: longitude stored as latitude (e.g. lat=37, lng=-7 for Dar es Salaam)
    if (lat > 15 && lat <= 90 && lng >= -90 && lng <= 15 && Math.abs(lat) > Math.abs(lng)) {
        return { lat: lng, lng: lat };
    }

    return { lat: lat, lng: lng };
}
