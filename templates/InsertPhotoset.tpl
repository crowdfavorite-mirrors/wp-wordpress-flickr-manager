<table class="describe">
	<tbody>
		<tr class="image-title">
			<th class="label" valign="top" scope="row">
				<label>[TitleLabel]</label>
			</th>
			<td class="field">
				[Title]
			</td>
		</tr>
		<tr class="image-description">
			<th class="label" valign="top" scope="row">
				<label>[DescriptionLabel]</label>
			</th>
			<td class="field">
				[Description]
			</td>
		</tr>
		<tr class="image-size">
			<th class="label" valign="top" scope="row">
				<label for="image-size-thumbnail">[SizeLabel]</label>
			</th>
			<td class="field">
				[Sizes]
			</td>
		</tr>
		<tr class="image-count">
			<th class="label" valign="top" scope="row">
				<label>[PhotosLabel]</label>
			</th>
			<td class="field">
				<input type="text" name="numPhotos" value="" style="width: 50px;" />
				<small>[NumPhotos]</small>
			</td>
		</tr>
		<tr>
			<th class="label" valign="top" scope="row">
				<label for="enableOverlay">
					[EnableOverlay]
				</label>
			</th>
			<td>
				<input type="checkbox" class="enableOverlay" id="enableOverlay" name="enableOverlay" [DefaultOverlay] /> 
			</td>
		</tr>
		<tr class="image-size overlayRow">
			<th class="label" valign="top" scope="row">
				<label for="image-size-thumbnail">
					[OverlaySizeLabel]
				</label>
			</th>
			<td class="field">
				[OverlaySizes]
			</td>
		</tr>
		<tr class="submit">
			<td></td>
			<td class="savesend">
				<input type="button" value="[InsertLabel]" id="sendSet" />
			</td>
		</tr>
	</tbody>
</table>