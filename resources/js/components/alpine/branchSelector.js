export default () => ({
    open: false,
    
    init() {
        this.portal = Alpine.store('portal');
    },
    
    get branches() {
        return this.portal.branches;
    },
    
    get currentBranch() {
        return this.portal.currentBranch;
    },
    
    selectBranch(branchId) {
        this.portal.switchBranch(branchId);
        this.open = false;
    },
    
    get currentBranchName() {
        return this.currentBranch?.name || 'Filiale wählen';
    },
    
    get isMultiBranch() {
        return this.branches.length > 1;
    }
});