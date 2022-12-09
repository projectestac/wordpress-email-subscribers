import '../css/style.css';
import GalleryItemsPage from './src/views/GalleryItemsPage';

if ( 'undefined' !== typeof wp.i18n ) {
    global.__ = wp.i18n.__;
} else {
    // Create a dummy fallback function incase i18n library isn't available.
    global.__ = ( text, textDomain ) => {
        return text;
    }
}

var canUpsellESTemplate = ( templatePlan ) => {
    let canUpsellTemplate = false;
    if ( 'lite' === ig_es_main_js_data.es_plan || 'trial' === ig_es_main_js_data.es_plan ) {
        canUpsellTemplate = templatePlan === 'starter' || templatePlan === 'pro';
    } else if ( 'starter' === ig_es_main_js_data.es_plan ) {
        canUpsellTemplate = templatePlan === 'pro';
    }
    return canUpsellTemplate;
}

global.canUpsellESTemplate = canUpsellESTemplate;

const campaignGalleryItemsWrapper = document.querySelector('#ig-es-campaign-gallery-items-wrapper');

let campaignType = location.search.split('campaign-type=')[1];
let campaignId   = location.search.split('campaign-id=')[1];
let manageTemplates = location.search.split('manage-templates=')[1];

if ( 'undefined' === typeof campaignType ) {
    campaignType = ig_es_main_js_data.post_notification_campaign_type;
}

if ( 'undefined' === typeof campaignId ) {
    campaignId = 0;
}

if ( 'undefined' === typeof manageTemplates ) {
    manageTemplates = 'no';
}



m.mount(
    campaignGalleryItemsWrapper, 
    {
        view: () => {
            return <GalleryItemsPage campaignId={campaignId} campaignType={campaignType} manageTemplates={manageTemplates}/>
        }
    }
);




