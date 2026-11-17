<?php

function renderModal($config = []) {
    $defaults = [
        'id' => 'modal',
        'title' => 'Modal Title',
        'icon' => '',
        'size' => 'md', 
        'content' => '',
        'footer' => '',
        'closeButton' => true,
        'backdrop' => true
    ];
    
    $config = array_merge($defaults, $config);
    
    $sizeClass = match($config['size']) {
        'sm' => 'max-width: 400px',
        'lg' => 'max-width: 700px',
        default => 'max-width: 500px'
    };
    ?>
    <div id="<?= htmlspecialchars($config['id']) ?>" class="custom-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 1050; align-items: center; justify-content: center;">
        <div class="modal-content" style="background: white; border-radius: 16px; width: 90%; <?= $sizeClass ?>; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: slideUp 0.3s ease-out;">
            
            
            <div class="modal-header" style="padding: 1rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                <h4 class="modal-title" style="font-size: 1.1rem; font-weight: 700; color: #111827; margin: 0;">
                    <?php if (!empty($config['icon'])): ?>
                        <i class="<?= htmlspecialchars($config['icon']) ?> mr-2" style="color: #0d9488;"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($config['title']) ?>
                </h4>
                <?php if ($config['closeButton']): ?>
                    <button class="modal-close" data-action="hide-element" data-target-id="<?= htmlspecialchars($config['id']) ?>" style="background: none; border: none; cursor: pointer;">
                        <i class="fas fa-times text-muted"></i>
                    </button>
                <?php endif; ?>
            </div>
            
            
            <div class="modal-body" style="padding: 1rem;">
                <?= $config['content'] ?>
            </div>
            
            
            <?php if (!empty($config['footer'])): ?>
                <div class="modal-footer" style="padding: 1rem; border-top: 1px solid #e5e7eb; text-align: right;">
                    <?= $config['footer'] ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($config['backdrop']): ?>
    <script>
        
        document.getElementById('<?= htmlspecialchars($config['id']) ?>').addEventListener('click', function(e) {
            if (e.target.id === '<?= htmlspecialchars($config['id']) ?>') {
                this.style.display = 'none';
            }
        });
        
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('<?= htmlspecialchars($config['id']) ?>').style.display = 'none';
            }
        });
    </script>
    <?php endif; ?>
    
    <style>
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .custom-modal.active {
            display: flex !important;
        }
    </style>
    <?php
}

function modalShowScript($modalId) {
    ?>
    <script>
        function showModal_<?= $modalId ?>() {
            document.getElementById('<?= htmlspecialchars($modalId) ?>').style.display = 'flex';
        }
        
        function hideModal_<?= $modalId ?>() {
            document.getElementById('<?= htmlspecialchars($modalId) ?>').style.display = 'none';
        }
    </script>
    <?php
}
?>
