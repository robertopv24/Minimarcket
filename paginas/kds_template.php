<?php
// Template logic and CSS for specialized KDS
// To be copy-pasted into kds_cocina_tv.php and kds_pizza_tv.php with station filtering

function renderTicket($orderData)
{
        $orden = $orderData['info'];
        $items = $orderData['items'];
        $diffSeconds = time() - strtotime($orden['created_at']);
        if ($diffSeconds < 0)
                $diffSeconds = 0;
        $mins = floor($diffSeconds / 60);
        $secs = $diffSeconds % 60;

        $bgStatus = ($orden['status'] == 'paid') ? 'status-paid' : 'status-preparing';
        $borderClass = ($mins > 25) ? 'late-warning' : (($mins > 15) ? 'medium-warning' : '');
        ?>
        <div class="ticket <?= $borderClass ?>">
                <div class="ticket-head <?= $bgStatus ?>">
                        <span>#<?= $orden['id'] ?> <small
                                        class="ms-1 opacity-75"><?= strtoupper($orden['cliente']) ?></small></span>
                        <span class="kds-timer" data-start-time="<?= strtotime($orden['created_at']) ?>"><i
                                        class="fa-regular fa-clock me-1"></i> <?= $mins ?>m
                                <?= str_pad($secs, 2, '0', STR_PAD_LEFT) ?>s</span>
                </div>
                <div class="ticket-body">
                        <?php foreach ($items as $it): ?>
                                <?php if ($it['is_main']): ?>
                                        <div class="main-item"># <?= strtoupper($it['name']) ?></div>
                                <?php endif; ?>

                                <?php if ($it['num'] > 0 || !$it['is_combo']): ?>
                                        <div class="sub-item-card">
                                                <div class="text-center">
                                                        <?php
                                                        $cType = $it['consumption_type'] ?? '';
                                                        $tier = $orden['delivery_tier'] ?? '';
                                                        $tagText = ($cType === 'delivery') ? 'DELIVERY' : ($it['is_takeaway'] ? 'LLEVAR' : 'MESA');
                                                        if ($cType === 'delivery' && !empty($tier))
                                                                $tagText .= " ($tier)";
                                                        ?>
                                                        <span
                                                                class="tag <?= $cType === 'delivery' ? 'tag-delivery' : ($it['is_takeaway'] ? 'tag-takeaway' : 'tag-dinein') ?>">
                                                                <?= $tagText ?>
                                                        </span>
                                                        <div class="item-num">#</div>
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

        .tag-takeaway {
                background: #ef4444;
                color: #fff;
        }

        .tag-delivery {
                background: #10b981;
                color: #fff;
        }

        .tag-dinein {
                background: #3b82f6;
                color: #fff;
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