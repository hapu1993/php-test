<script id="template-upload" type="text/x-tmpl">
	{% for (var i=0, file; file=o.files[i]; i++) { %}
		<tr class="template-upload fade">
			<td colspan="3">
				<div class="meta">
					<b>{%=file.name%}</b><br/>
					{%=o.formatFileSize(file.size)%}
				</div>
				
				{% if (!o.files.error) { %}
					<div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
						<div class="bar" style="width:0%;"></div>
					</div>
				{% } %}
			
				{% if (file.error) { %}
					<div class="error">Error: {%=file.error%}</div>
				{% } %}
			
			</td>
		</tr>
	{% } %}
</script>

<script id="template-download" type="text/x-tmpl">
	{% for (var i=0, file; file=o.files[i]; i++) { %}
		<tr class="template-download fade">
			{% if (file.error) { %}
				<td class="name ui-state-error" colspan="2">
					<b>Error: {%=file.error%}</b><br/>
					{%=o.formatFileSize(file.size)%}
				</td>

			{% } %}
			
			{% if (!file.error) { %}
				<td class="preview">
					{% if (file.thumb) { %}
						<a class="image" href="{%=file.url%}" target="_blank"><img src="{%=file.thumb%}"></a>
					{% } %}
					
					{% if (!file.thumb) { %}
						<span class="ext">{%=file.file_type%}</span>
					{% } %}
				</td>
				
				<td class="name">
					<a href="{%=file.url%}" target="_blank">{%=file.nice_name%}</a><br/>
					{%=o.formatFileSize(file.size)%}
				</td>
			{% } %}
		
			<td class="file_actions">
				<a data-file-upload-delete="true" class="action tooltip" title="Delete file" href="{%=file.delete_url%}"><i class="fa fa-times"></i></a>
				{% if (file.thumb) { %}
					<span data-file-upload-rotate="true" class="action tooltip" title="Rotate image" data-path="{%=file.filepath%}" data-rcounter="1"><i class="fa fa-rotate-right"></i></span>
					<span data-file-upload-resize="true" class="action tooltip" title="Resize image" data-path="{%=file.filepath%}"><i class="fa fa-arrows"></i></span>
					<a class="action tooltip" title="Open image in new window" target="_blank" href="{%=file.url%}"><i class="fa fa-external-link"></i></a>
				{% } %}
			</td>

		</tr>
	{% } %}
</script>