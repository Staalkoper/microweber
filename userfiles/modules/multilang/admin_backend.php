<div class="mw-module-admin-wrap">

	<module type="admin/modules/info" />

	<?php require 'admin_settings.php'; ?>

	<?php $translations = DB::table('translations')->get(); ?>

	<h3>
		Available Translations
		(<?php echo count($translations); ?>)
	</h3>

	<table width="100%">
	<thead>
		<tr>
			<th width="100">Language</th>
			<th width="100">Source ID</th>
			<th width="100">Source Type</th>
			<th>Translated Data</th>
		</tr>
	<thead>
	<?php if(count($translations)): ?>
	<?php foreach($translations as $translation): ?>
		<tr>
			<td valign="top">
				<?php echo $translation->lang; ?>
				<div class="mw-language-tag"><?php echo $translation->lang; ?></div>
				<small>#<?php echo isset($translation->id) ? $translation->id : '?'; ?></small>
			</td>
			<td valign="top" align="center"><?php echo $translation->translatable_id; ?></td>
			<td valign="top" align="center"><?php echo $translation->translatable_type; ?></td>
			<td>
			<?php $json = json_decode($translation->translation); ?>
			<?php if($json): ?>
			<table width="100%" style="background: #eee">
				<?php foreach($json as $key => $value): ?>
				<tr>
					<td width="100" valign="top"><b><?php echo $key; ?></b></td>
					<td><?php echo '<b>['. strlen($value) . ']</b> ' . str_limit($value, 512); ?></td>
				</tr>
				<?php endforeach; ?>
			</table>
			<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; ?>
	<?php endif; ?>
	</table>
</div>
