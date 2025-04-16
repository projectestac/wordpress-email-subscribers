const { select, dispatch } = wp.data;
const { serialize, createBlock,unregisterBlockVariation  } = wp.blocks;
const { __ } = wp.i18n;


function hideUnwantedGutenbergPanels() {
    const panelsToRemove = [
        'post-status',
        'taxonomy-panel-category',
        'taxonomy-panel-post_tag',
        'template',
        'post-link',
        'featured-image',
        'discussion-panel',
        'author',
        'post-excerpt'
    ];

    const dispatcher = dispatch('core/edit-post');
    panelsToRemove.forEach(panel => {
        dispatcher.removeEditorPanel(panel);
    });

}

function enableFullscreenMode() {
    const isFullscreen = select('core/edit-post').isFeatureActive('fullscreenMode');
    if (!isFullscreen) {
        dispatch('core/edit-post').toggleFeature('fullscreenMode');
    }
}

function hideDefaultCPTButtons() {
   
    const publishBtn = document.querySelector('.editor-header__settings .editor-post-publish-button__button');
    const editorNotice = document.querySelector('.components-editor-notices__dismissible');
    const addTitle = document.querySelector('.editor-visual-editor__post-title-wrapper');
    const postBackBtnLink = document.querySelector('.editor-header__back-button a');

    if (editorNotice) editorNotice.style.display = 'none';
    if (publishBtn) publishBtn.style.display = 'none';
    if (addTitle) addTitle.style.display = 'none';
    if (postBackBtnLink) {
        postBackBtnLink.setAttribute('aria-label', 'Back to Express Dashboard');
        postBackBtnLink.href = 'admin.php?page=es_dashboard';
        postBackBtnLink.setAttribute('title', 'Back to Express Dashboard');
    }

}

//Function to add default gutenberg content
function insertDefaultGutenbergBlocks() {

    const blocks = select("core/block-editor").getBlocks();
    if (!Array.isArray(blocks) || blocks.length > 0) return;

    const siteLogoImg = ig_es_gutenberg_mjml_ajax.site_logo_img;
    const defaultBlocks = [
        // Container group with max-width
        createBlock("core/group", {
            layout: { type: "constrained" },
            style: {
                spacing: { padding: { top: "20px", bottom: "20px", right: "0px", left: "0px" } },
                color: {
                    background: "#fff"
                },
                layout: { contentSize: "600px", wideSize: "600px" },
                typography: { fontFamily: "georgia, serif" }
            }
        }, [

            createBlock("core/group", {
                layout: { contentSize: "150px" } },[
                createBlock("core/image", {
                    align: "center",
                    url: ig_es_gutenberg_mjml_ajax.site_logo_url ? ig_es_gutenberg_mjml_ajax.site_logo_url : 'https://webstockreview.net/images/sample-png-images-14.png',
                })
            ]
            )
            ,

            // Centered Heading
            createBlock("core/heading", {
                level: 2,
                content: "Welcome to Your Email Campaign",
                textAlign: "center"
            }),

            // Intro Paragraph
            createBlock("core/paragraph", {
                content: "Start crafting your perfect email campaign with this template.",
                align: "center"
            }),

            // Optional image
            createBlock("core/image", {
                align: "center",
                url: "https://www.icegram.com/gallery/wp-content/uploads/2022/11/istockphoto-1091058068-612x612.jpg"
            }),

            // Bullet points
            createBlock("core/list", {
                values: "<li>Highlight important points</li><li>Engage your audience</li><li>Drive more conversions</li>",
                style: {
                    spacing: {
                        padding: {
                            top: "20px",
                            right: "30px",
                            bottom: "20px",
                            left: "30px"
                        }
                    }
                }
            }),

            createBlock("core/buttons", {
                layout: { type: "flex", justifyContent: "center" }
            }, [
                createBlock("core/button", {
                    text: "Start Crafting Now",
                    style: {
                        color: { background: ig_es_gutenberg_mjml_ajax.site_colors[0], text: ig_es_gutenberg_mjml_ajax.site_colors[1] },
                        border: { radius: "4px" }
                    }
                })
            ]),

            // Footer Group
            createBlock("core/group", {
                // backgroundColor: "f3f3f3",
                style: {
                    spacing: { padding: { top: "20px", bottom: "20px", right: "0px", left: "0px" } },
                    color: { background: "#f3f3f3" },
                    typography: { fontSize: "14px" }
                }
            }, [
                // Social links
                createBlock("core/social-links", { align: "center" }, [
                    createBlock("core/social-link", { service: "facebook" }),
                    createBlock("core/social-link", { service: "twitter" }),
                    createBlock("core/social-link", { service: "linkedin" })
                ]),
                createBlock("core/paragraph", {
                    align: "center",
                    content: '© 2025 ' + ( ig_es_gutenberg_mjml_ajax.site_name ? ig_es_gutenberg_mjml_ajax.site_name : __( 'Your Brand Name', 'email-subscribers' ) ),
                }),
                createBlock("core/paragraph", {
                    align: "center",
                    content: 'You are receiving this email because you have visited our site or asked about our regular newsletter. If you wish to unsubscribe from our newsletter, <a href="{{UNSUBSCRIBE-LINK}}">click here</a>.',
                })
            ])
        ])
    ];

    dispatch("core/block-editor").insertBlocks(defaultBlocks);
}

const updateColorPalette = () => {
     
    const { getSettings } = wp.data.select('core/block-editor');
    const settings = getSettings();
    const colors = settings.colors.reduce(
        ( _colors, { slug, color } ) => ( { ..._colors, [ slug ]: color } ),
        {}
    );
    

    wp.apiFetch( { 
        path: 'icegram-express/v1/color-palette',
        method: 'POST',
        data: { ...colors },
    })
    .catch(error => {
        console.error(__('Ajax error:', 'email-subscribers'), error);
    });
};

//Show toast message
function showMessageInToast(message, type = 'success') {
    const noticeType = type === 'error' ? 'error' : 'success';
    
        dispatch('core/notices').createNotice(
            noticeType,
            message,
            {
                type: 'snackbar', 
                isDismissible: true,
            }
        );
    
}

function removeRowAndGridVariationsOfGroupBlock() {
    
    try {
        ['group-row', 'group-grid'].forEach((variationName) => {
            unregisterBlockVariation('core/group', variationName);
        });

    } catch (e) {
        console.warn(__('Could not unregister group variations yet:', 'email-subscribers'), e);
    }
}

function hideToolTip() {
    const tooltipPortals = document.querySelectorAll('[id^="portal/tooltip"]');
    tooltipPortals.forEach(el => {
        el.style.display = 'none';
    });

}

wp.domReady(() => {

    enableFullscreenMode();
    hideUnwantedGutenbergPanels();
    hideDefaultCPTButtons();
    insertDefaultGutenbergBlocks();
    removeRowAndGridVariationsOfGroupBlock();
    hideToolTip();
    // Setup MutationObserver
    const observer = new MutationObserver(() => {
        hideDefaultCPTButtons();
        insertDefaultGutenbergBlocks();
        removeRowAndGridVariationsOfGroupBlock();
        hideToolTip();
    });
    observer.observe(document.body, { childList: true, subtree: true });

    // TODO: domReady fires as soon as page is loaded. Find suitable event when Gutenberg editor is completely loaded to hook updateColorPalette functions
    setTimeout(function(){
        updateColorPalette();
    },3000);

    // Function to process Gutenberg to MJML 
    const convertToMJML = () => {
     
        const blocks = select("core/block-editor").getBlocks();

        if (!blocks.length) {
            showMessageInToast(__('No content found in the editor.Please add some blocks.', 'email-subscribers'),'error');
            return;
        }
        const contentHTML = serialize(blocks);

        fetch(ig_es_gutenberg_mjml_ajax.ajaxurl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                action: "ig_es_convert_to_mjml",
                content: contentHTML,
                _wpnonce: ig_es_gutenberg_mjml_ajax.nonce
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.mjmlData = { mjmlContent: data.data.html };
                    convertMJMLToHTML(window.mjmlData.mjmlContent);
                } else {
                    console.error(__("Conversion failed:", "email-subscribers"), data.data?.message || __("Unknown error", "email-subscribers"));
                }
            })
            .catch(error => {
                console.error(__("Ajax error:", "email-subscribers"), error);
            });
    };

    // Function to process MJML to HTML
    function convertMJMLToHTML(mjmlContent) {

        if (typeof window.mjml === "function") {
            try {
                const convertedHtml = window.mjml(mjmlContent).html;
               // console.log(__("Converted HTML:", "email-subscribers"), convertedHtml);
              
                showMessageInToast(__('Gutenberg Content Copied to Camapign!', 'email-subscribers'));
                sessionStorage.setItem('ig_es_gutenberg_poc_content', convertedHtml);
                sessionStorage.setItem('ig_es_triggered_redirect', 'yes');
                window.location.href = "admin.php?page=es_campaigns#!/campaign/edit?campaignType=newsletter&editorType=classic";

            } catch (error) {
                console.error(__("MJML Conversion Error:", "email-subscribers"), error);
            }
        } else {
            console.error(__("mjml-browser not loaded.", "email-subscribers") );
        }
    }
     
    // Function to copy gutenberg content 
    function addCopyToCampaignButton() {
        let toolbar = document.querySelector('.editor-header__settings');
        if (!toolbar) return;

        // Avoid adding multiple buttons
        if (document.querySelector('#copy-to-campaign-btn')) return;

        // Create the button
        let button = document.createElement("button");
        button.id = "copy-to-campaign-btn";
        button.innerText = "Copy to Campaign";
        button.className = "components-button is-primary";
        button.style.marginRight = "5px"; 
        
        button.addEventListener("click", convertToMJML);

        toolbar.prepend(button);
    }
    addCopyToCampaignButton(); 
    const copyToCampaignObserver = new MutationObserver(addCopyToCampaignButton);
    copyToCampaignObserver.observe(document.body, { childList: true, subtree: true });
    
});  // End of domReady



