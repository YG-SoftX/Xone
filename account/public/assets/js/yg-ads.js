/**
 * YG Ads External SDK (v1.0.0)
 * Sovereign Advertising Expansion Protocol
 */
(function() {
    const YG_ADS_API = 'https://account.ygxone.com/api/ads/serve';

    class YgAds {
        constructor() {
            this.init();
        }

        init() {
            // Find all ad containers on the page
            const containers = document.querySelectorAll('.yg-ad-slot');
            containers.forEach(slot => {
                this.loadAd(slot);
            });
        }

        async loadAd(slot) {
            const clientId = slot.getAttribute('data-client-id');
            const slotId = slot.getAttribute('data-slot-id');

            if (!clientId || !slotId) return;

            try {
                const response = await fetch(`${YG_ADS_API}?client_id=${clientId}&slot_id=${slotId}`);
                const data = await response.json();

                if (data.success && data.ad) {
                    this.renderAd(slot, data.ad);
                }
            } catch (e) {
                console.error('YG Ads: Failed to load ad unit.');
            }
        }

        renderAd(slot, ad) {
            slot.innerHTML = `
                <div style="font-family: 'Inter', sans-serif; border: 1px solid #eee; border-radius: 12px; overflow: hidden; max-width: 100%;">
                    <a href="${ad.target_url}" target="_blank" style="text-decoration: none; display: flex; gap: 12px; padding: 12px; background: #fff;">
                        <img src="${ad.image_url}" style="width: 80px; hieght: 80px; object-fit: cover; border-radius: 8px;">
                        <div style="flex: 1;">
                            <h5 style="margin: 0 0 4px; font-size: 14px; color: #333;">${ad.title}</h5>
                            <p style="margin: 0 0 8px; font-size: 11px; color: #666; line-clamp: 2; overflow: hidden;">${ad.content}</p>
                            <span style="font-size: 9px; font-weight: bold; color: #1a73e8; text-transform: uppercase; letter-spacing: 0.5px;">YG Ads</span>
                        </div>
                    </a>
                </div>
            `;
        }
    }

    // Initialize the SDK
    window.YgAds = new YgAds();
})();
