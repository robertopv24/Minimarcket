<?php
// Template logic and CSS for specialized KDS
// To be copy-pasted into kds_cocina_tv.php and kds_pizza_tv.php with station filtering

function renderTicket($orderData)
{
        $orden = $orderData['info'];
        $items = $orderData['items'];
        $mins = round((time() - strtotime($orden['created_at'])) / 60);
        if ($mins < 0)
                $mins = 0;

        $bgStatus = ($orden['status'] == 'paid') ? 'status-paid' : 'status-preparing';
        $borderClass = ($mins > 25) ? 'late-warning' : (($mins > 15) ? 'medium-warning' : '');
        ?>
        <div class="ticket <?= $borderClass ?>">
                <div class="ticket-head <?= $bgStatus ?>">
                        <span>#<?= $orden['id'] ?> <small
                                        class="ms-1 opacity-75"><?= strtoupper($orden['cliente']) ?></small></span>
                        <span><i class="fa-regular fa-clock me-1"></i> <?= $mins ?>m</span>
                </div>
                <div class="ticket-body">
                        <?php foreach ($items as $it): ?>
                                <?php if ($it['is_main']): ?>
                                        <div class="main-item"><?= $it['qty'] ?> x <?= strtoupper($it['name']) ?></div>
                                <?php endif; ?>

                                <?php if ($it['num'] > 0 || !$it['is_combo']): ?>
                                        <div class="sub-item-card">
                                                <div class="text-center">
                                                        <span class="tag <?= $it['is_takeaway'] ? 'tag-takeaway' : 'tag-dinein' ?>">
                                                                <?= $it['is_takeaway'] ? 'LLEVAR' : 'MESA' ?>
                                                        </span>
                                                        <div class="item-num">#<?= $it['num'] ?></div>
                                                        <div class="item-name">(<?= strtoupper($it['name']) ?>)</div>
                                                </div>

                                                <?php if (!empty($it['mods'])): ?>
                                                        <div class="mods-container mt-2">
                                                                <?php foreach ($it['mods'] as $m): ?>
                                                                        <div class="mod-line <?= (strpos($m, 'SIN') !== false) ? 'mod-bad' : 'mod-good' ?>">
                                                                                <?= strtoupper($m) ?>
                                                                        </div>
                                                                <?php endforeach; ?>
                                                        </div>
                                                <?php endif; ?>

                                                <?php if ($it['note']): ?>
                                                        <div class="item-note">
                                                                <i class="fa-solid fa-comment-dots me-1"></i> <?= strtoupper($it['note']) ?>
                                                        </div>
                                                <?php endif; ?>
                                        </div>
                                <?php endif; ?>
                        <?php endforeach; ?>
                </div>
        </div>
<?php } ?>

<style>
        /* CSS additions for vertical layout */
        .sub-item-card {
                background: #f8fafc;
                border-radius: 12px;
                padding: 0.75rem;
                margin-bottom: 0.75rem;
                border: 1px solid #e2e8f0;
        }

        .item-num {
                font-size: 1.25rem;
                font-weight: 800;
                color: #1e293b;
                margin: 0.25rem 0;
        }

        .item-name {
                font-size: 1.1rem;
                font-weight: 600;
                color: #334155;
        }

        .tag {
                display: inline-block;
                padding: 1px 10px;
                font-size: 0.75rem;
                letter-spacing: 0.5px;
        }

        .mod-line {
                font-size: 0.95rem;
                font-weight: 700;
                padding: 2px 0;
        }

        .mod-bad {
                color: #ef4444;
        }

        /* Matches -- SIN */
        .mod-good {
                color: #16a34a;
        }

        /* Matches 🔘 and ++ EXTRA */
        .item-note {
                margin-top: 0.5rem;
                padding: 0.4rem;
                background: #fffbeb;
                color: #92400e;
                border-radius: 6px;
                font-size: 0.85rem;
                font-weight: 600;
        }
</style>