<style>
	.plugins .html-forms-premium-after-plugin-row th,
	.plugins .html-forms-premium-after-plugin-row td {  
		background-color: lightYellow; 
		box-shadow: inset 0 -1px 0 rgba(0,0,0,0.1); 
	}
</style>
<tr class="active html-forms-premium-after-plugin-row">
	<th scope="row" class="check-column"></th>
	<td colspan="3">
		<?php 
        echo sprintf( __('Please <a href="%s">activate your HTML Forms Premium license</a> to receive plugin updates.', 'html-forms-premium'), admin_url('admin.php?page=html-forms-settings'));
		echo ' ';
        echo sprintf( __('Need a license key? <a href="%s" target="_blank">Purchase one here</a>.', 'html-forms-premium'), 'https://htmlformsplugin.com/premium/');
		?>
	</td>
</tr>
