@props(['compact' => false])

{{-- Minimal site footer component with inline heart SVG so icon shows even when mdi fonts aren't loaded. --}}
<div class="lonyo-footer-bottom-text">
	<div class="container-fluid">
		<div class="row">
			<div class="col fs-13 text-muted text-center">
				&copy; <script>document.write(new Date().getFullYear())</script> - Made with
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" class="text-danger mx-1" aria-hidden="true" focusable="false" style="vertical-align:-0.12em;">
					<path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 6 4 4 6.5 4 8.04 4 9.57 4.81 10.54 6.09L12 8.1l1.46-2.01C14.43 4.81 15.96 4 17.5 4 20 4 22 6 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
				</svg>
				by <a href="#!" class="text-reset fw-semibold">Hepta Infotech Services LLP</a>
			</div>
		</div>
	</div>
</div>
