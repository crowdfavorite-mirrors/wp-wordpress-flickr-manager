<table class="describe">
	<thead class="media-item-info">
		<tr>
			<td rowspan="4" width="10%">
				<img src="[Thumbnail]" alt="[Title]" />
			</td>
			<td>
				[File]
			</td>
		</tr>
		<tr>
			<td>
				image/jpg
			</td>
		</tr>
		<tr>
			<td>
				[Uploaded]
			</td>
		</tr>
	</thead>
	<tbody>
		<tr class="post_title form-required">
			<th class="label" valign="top" scope="row">
				<label for="title">
					<span class="alignleft">[TitleLabel]</span>
					<span class="alignright">
						<abbr class="required" title="required">*</abbr>
					</span>
					<br class="clear"/>
				</label>
			</th>
			<td class="field">
				<input id="title" type="text" value="[Title]" name="title" />
			</td>
		</tr>
		<tr class="post_tags">
			<th class="label" valign="top" scope="row">
				<label for="tags">
					[TagsLabel]
				</label>
			</th>
			<td class="field">
				<input type="text" id="tags" name="tags" value="[Tags]" />
				<p class="help">*[SpaceSeparated]</p>
			</td>
		</tr>
		<tr class="post_content">
			<th class="label" valign="top" scope="row">
				<label for="description">
					[DescriptionLabel]
				</label>
			</th>
			<td class="field">
				<textarea id="description" name="description" rows="" cols="">[Description]</textarea>
			</td>
		</tr>
		<tr class="url">
			<th class="label" valign="top" scope="row">
				<label for="link">
					[LinkLabel]
				</label>
			</th>
			<td class="field">
				<a href="[Link]" target="_blank" title="[Link]">[Link]</a>
				<input type="hidden" name="link" id="link" value="[Link]" />
			</td>
		</tr>
		<tr class="align">
			<th class="label" valign="top" scope="row">
				<label for="align-none">
					[AlignmentLabel]
				</label>
			</th>
			<td class="field">
				<input id="align-none" type="radio" checked="checked" value="none" name="align" />
				<label class="align image-align-none-label" for="align-none">[AlignNone]</label>
				<input id="align-left" type="radio" value="left" name="align" />
				<label class="align image-align-left-label" for="align-left">[AlignLeft]</label>
				<input id="align-center" type="radio" value="center" name="align" />
				<label class="align image-align-center-label" for="align-center">[AlignCentre]</label>
				<input id="align-right" type="radio" value="right" name="align" />
				<label class="align image-align-right-label" for="align-right">[AlignRight]</label>
			</td>
		</tr>
		<tr class="image-size">
			<th class="label" valign="top" scope="row">
				<label for="image-size-thumbnail">
					[SizeLabel]
				</label>
			</th>
			<td class="field">
				[Sizes]
			</td>
		</tr>
		<tr>
			<th class="label" valign="top" scope="row">
				<label for="enableOverlay">
					[EnableOverlay]
				</label>
			</th>
			<td>
				<input type="checkbox" class="enableOverlay" id="enableOverlay" name="enableOverlay" checked="[DefaultOverlay]" /> 
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
		<tr class="overlayRow">
			<th class="label" valign="top" scope="row">
				<label for="setName">
					[SetLabel]
				</label>
			</th>
			<td class="field">
				<input type="text" name="setName" id="setName" value="[SetName]" />
			</td>
		</tr>
		<tr class="submit">
			<td></td>
			<td class="savesend">
				<input class="button insertPhoto" type="button" value="[InsertLabel]" />
				<span class="ownerControls">[OwnerControls]</span>
			</td>
		</tr>
	</tbody>
</table>
