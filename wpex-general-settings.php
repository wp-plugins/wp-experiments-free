<div class='wrap'>
	<div id="icon-edit-pages" class="icon32 icon32-posts-page"><br></div>
	<h2>Title Experiments Settings</h2>
	
	<form method="post">
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><label for="blogname">Use JavaScript</label></th>
					<td>
						<input name="use_js" type="checkbox" value="1" <?php if($use_js): ?>checked<?php endif; ?>> Use JavaScript to set titles
						<p class="description">Use JavaScript to set the titles. Use this if your site uses heavy caching.</p>
					</td>
				</tr>
				<tr>
					<th></th>
					<td><input type="submit" class="button-primary" name="save" value="Save Settings" /></td>
				</tr>
			</tbody>
		</table>
	</form>
</div>