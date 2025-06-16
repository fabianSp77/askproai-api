<!-- Livewire Scripts -->
<script src="{{ asset('vendor/livewire/livewire.js') }}" data-csrf="{{ csrf_token() }}" data-update-uri="{{ url('/livewire/update') }}" data-navigate-once="true"></script>
{{-- Temporarily disabled: livewire-fix.js was causing navigation issues --}}
{{-- <script src="{{ asset('js/livewire-fix.js') }}"></script> --}}