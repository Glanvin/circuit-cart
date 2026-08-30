<div class="container-fluid p-4">
    <div class="table-responsive">
        <table class="table table-striped-columns table-bordered shadow align-middle text-center caption-top">
            
            <caption class="px-2 pb-3 text-muted">
                The SKU Colomn format follows <b>[PART]-[BRAND]-[MODEL]</b>. <br>
                For example the part <strong>SCR-APL-IP11</strong>, represents 
                <span class="badge text-bg-info">SCR</span> (Screen) &minus; 
                <span class="badge text-bg-info">APL</span> (Apple) &minus; 
                <span class="badge text-bg-info">IP11</span> (iPhone 11).
            </caption>

            <thead>
                <tr>
                    <th scope="col">Part ID</th>
                    <th scope="col">SKU</th>
                    <th scope="col">Name & Category</th>
                    <th scope="col">Cost (₱)</th>
                    <th scope="col">Retail (₱)</th>
                    <th scope="col">In-Stock</th>
                    <th colspan="2">Actions</th>
                </tr>
            </thead>
            <tbody class="table-group-divider">
                <?php if (!empty($parts)): ?>
                    <?php foreach ($parts as $part): ?>
                        <tr>
                            <th scope="row">
                                <?= html_escape($part['part_id']) ?>
                            </th>
                            <td>
                                <span class="text-uppercase font-monospace"><?= html_escape($part['sku']) ?></span>
                            </td>
                            <td class="text-start">
                                <strong><?= html_escape($part['part_name']) ?></strong><br>
                                <small class="text-muted"><?= html_escape($part['device_type']) ?> - <?= html_escape($part['category']) ?></small>
                            </td>
                            <td>
                                <?= number_format($part['cost_price'], 2) ?>
                            </td>
                            <td class="text-success fw-bold">
                                <?= number_format($part['selling_price'], 2) ?>
                            </td>
                            <td>
                                <?php if ($part['stock_quantity'] <= 0): ?>
                                    <span class="badge text-bg-danger fs-6"><strong>Out of Stock</strong></span>
                                <?php else: ?>
                                    <span class="fs-6"><?= html_escape($part['stock_quantity']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="#" class="btn btn-sm btn-outline-warning">Edit Item</a>
                            </td>
                            <td>
                                <a href="#" class="btn btn-sm btn-outline-success">Add Stock</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No inventory parts found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>