	<script type="text/javascript" src="<?php _e( get_module_path($this, "assets/plugins/izitoast/js/izitoast.js") )?>"></script>
	<script type="text/javascript" src="<?php _e( get_module_path($this, "assets/js/payment.js") )?>"></script>
	<script type="text/javascript" src="<?php _e( get_module_path($this, "assets/js/core.js") )?>"></script>
	<script language="JavaScript">
$(document).ready(function() {
$("#enviar").click(function( e ){
e.preventDefault();

var width = 550;
var height = 550;

var left = 99;
var top = 99;

window.open(enviar,'janela', 'width='+width+', height='+height+',top='+top+'left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');

 });
 });
</script>
</body>
</html>