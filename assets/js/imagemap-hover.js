// /assets/js/imagemap-hover.js
const wrapper = document.querySelector('.amp-css-imagemap-wrapper');
if (wrapper) {
    const mapId = wrapper.dataset.mapId;
    console.log(`AMP Image Map #${mapId} Initialized.`);

    const listItems = document.querySelectorAll('.hotspot-list-ui li button, .hotspot-list-ui li a');
    const imageHotspots = document.querySelectorAll('.hotspot-on-image');

    const clearActiveStates = () => {
        imageHotspots.forEach(spot => spot.classList.remove('active-hover'));
    };

    listItems.forEach(item => {
        const targetId = item.id.replace('hotspot-list-', 'hotspot-image-');
        const targetHotspot = document.getElementById(targetId);

        if (targetHotspot) {
            item.addEventListener('mouseover', () => {
                clearActiveStates();
                targetHotspot.classList.add('active-hover');
            });
            item.addEventListener('mouseout', clearActiveStates);
        }
    });

    // Thêm một class để báo hiệu script đã chạy thành công
    wrapper.classList.add('script-loaded');
}