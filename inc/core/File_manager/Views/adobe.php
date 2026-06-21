<?php $adobe_client_id = trim((string) get_option("fm_adobe_client_id", "")); ?>
<?php if ($button && $adobe_client_id !== ""): ?>
<a class="dropdown-item ccEverywhere" href="javascript:void(0)"><img src="<?php _ec( get_module_path( __DIR__, "Assets/img/adobe.png") )?>" class="w-17 h-17"> <?php _e("Adobe Express")?></a>
<script src="https://sdk.cc-embed.adobe.com/v2/CCEverywhere.js"></script>
<script type="text/javascript">
	(async () => {
        if (!window.CCEverywhere || typeof window.CCEverywhere.initialize !== "function") {
            return;
        }

	    window.ccEverywhere = await window.CCEverywhere.initialize({
            clientId: '<?php _ec($adobe_client_id)?>',
            appName: "Adobe Express",
            appVersion: { major: 1, minor: 0 },
            platformCategory: 'web',
            redirectUri: '<?php _ec( base_url() )?>'
        });
	})();

	$(function(){
		$( document ).on( 'click', '.ccEverywhere', function (e) {
	        e.preventDefault();

            if (!window.ccEverywhere || typeof window.ccEverywhere.createDesign !== "function") {
                console.warn('Adobe Express is not ready yet.');
                return;
            }

	        const d = new Date();
			let time = d.getTime();

	        $("body").append("<div class='run_adobe run_adobe_"+time+"'><div>");

	        setTimeout(function(){
	        	window.ccEverywhere.createDesign({
		            callbacks: {
		                onCancel: () => {
		                	$(".run_adobe"+time).remove();
		                },
		                onPublish: (publishParams) => {
		                	File_manager.saveFile(publishParams.asset.data);
		                   	$(".run_adobe"+time).remove();
		                },
		                onError: (err) => {
		                	$(".run_adobe"+time).remove();
		                    console.error('Error received is', err.toString());
		                }
		            },
		            outputParams: {
		                outputType: "base64"
		            }
		        });
	        }, 1000);
	    });
	});
</script>
<?php endif ?>
