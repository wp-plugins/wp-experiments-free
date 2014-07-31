<div class='wrap'>
	<div id="icon-edit-pages" class="icon32 icon32-posts-page"><br></div>
	<h2>Title Experiments Settings</h2>
	
	<form method="post">
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><label for="use_js">Use JavaScript</label></th>
					<td>
						<input name="use_js" type="checkbox" value="1" <?php if($use_js): ?>checked<?php endif; ?>> Use JavaScript to set titles
						<p class="description">Use JavaScript to set the titles. Use this if your site uses heavy caching.</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="best_feed">Best title in feed</label></th>
					<td>
						<input name="best_feed" type="checkbox" value="1" <?php if($best_feed): ?>checked<?php endif; ?>> Use the best performing title in feeds
						<p class="description">Use the best performing title in feeds instead of the default title.</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="adjust_every">Recalculate every</label></th>
					<td>
						<select name="adjust_every">
							<option value="0" <?php if($adjust_every == 0): ?>selected<?php endif; ?>>Instantly</option>
							<option value="300" <?php if($adjust_every == 300): ?>selected<?php endif; ?>>5 minutes</option>
							<option value="1800" <?php if($adjust_every == 1800): ?>selected<?php endif; ?>>30 minutes</option>
							<option value="3600" <?php if($adjust_every == 3600): ?>selected<?php endif; ?>>1 hour</option>
							<option value="7200" <?php if($adjust_every == 7200): ?>selected<?php endif; ?>>2 hours</option>
							<option value="14400" <?php if($adjust_every == 14400): ?>selected<?php endif; ?>>4 hours</option>
							<option value="28800" <?php if($adjust_every == 28800): ?>selected<?php endif; ?>>8 hours</option>
						</select>
						<p class="description">Recalculate title display probabilities every so often. Doing it too often can slow down high traffic sites.</p>
					</td>
				</tr>
				<?php if($titleEx): ?>
					<?php echo $titleEx->settings(); ?>
				<?php else: ?>
					<tr valign="top">
					<th scope="row"><label for="best_feed">Want more?</label></th>
					<td>
						<p class="description"><b>Do you want to get more out of your Title Experiments?</b><br/>
						 If so, check out <a target="_blank" href="https://wpexperiments.com/title-experiments-pro/">Title Experiments Pro</a>. You can get more detailed statistics, embedded priority support, and more!.</p>
					</td>
				</tr>
				<?php endif; ?>
				<tr>
					<th></th>
					<td><input type="submit" class="button-primary" name="save" value="Save Settings" /></td>
				</tr>
			</tbody>
		</table>
	</form>
	<p>For more information, visit <a href='https://wpexperiments.com/title-experiments/'>wpexperiments.com/title-experiments/</a></p>
</div>