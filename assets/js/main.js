// Hospital Information System - Main JavaScript Functions

// Modal Functions
function openLainLainModal() {
    const modal = document.getElementById('lainLainModal');
    const content = document.getElementById('lainLainContent');
    
    if (window.lainLainData && window.lainLainData.trim() !== '') {
        content.innerHTML = window.lainLainData;
    } else {
        content.innerHTML = '<p class="text-gray-500 italic">Tiada maklumat tambahan tersedia.</p>';
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeLainLainModal() {
    const modal = document.getElementById('lainLainModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function openLainDeptModal() {
    const modal = document.getElementById('lainDeptModal');
    const content = document.getElementById('lainDeptContent');
    
    if (window.lainDeptData && window.lainDeptData.trim() !== '') {
        content.innerHTML = window.lainDeptData;
    } else {
        content.innerHTML = '<p class="text-gray-500 italic">Tiada maklumat tambahan tersedia.</p>';
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeLainDeptModal() {
    const modal = document.getElementById('lainDeptModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Initialize event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Close modal when clicking outside
    const lainLainModal = document.getElementById('lainLainModal');
    if (lainLainModal) {
        lainLainModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeLainLainModal();
            }
        });
    }

    const lainDeptModal = document.getElementById('lainDeptModal');
    if (lainDeptModal) {
        lainDeptModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeLainDeptModal();
            }
        });
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLainLainModal();
            closeLainDeptModal();
        }
    });
});