@once
    {{-- Cal.com Atoms Scripts & Styles --}}
    @vite(['resources/js/calcom-atoms.jsx', 'resources/css/calcom-atoms.css'])

    <script>
        // Global Cal.com configuration
        window.CalcomConfig = {
            teamId: {{ config('calcom.team_id') }},
            teamSlug: '{{ config('calcom.team_slug') }}',
            apiUrl: '{{ url('') }}', // Use our Laravel proxy (root-level routes in web.php)
            defaultBranchId: {{ auth()->user()?->branch_id ?? 'null' }},  // For company_manager with assigned branch
            companyId: {{ auth()->user()?->company_id ?? 'null' }},
            layout: 'MONTH_VIEW',  // Will be replaced with user preference in Phase 4
            autoSelectSingleBranch: true,  // Auto-select if user has only one branch
        };
    </script>
@endonce
