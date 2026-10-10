<nav class="navbar navbar-expand-lg">
    <div class="container-fluid mx-3">
        <a class="navbar-brand" href="#">
            <?= SITE_NAME ?>
        </a>
        <button aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation" class="navbar-toggler" data-bs-target="#navbarSupportedContent" data-bs-toggle="collapse" type="button">
            <span class="navbar-toggler-icon">
            </span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a aria-current="page" class="nav-link <?php if(strtolower($title) == 'home') echo 'active';?>" href="<?= site_url('')?>">
                        Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php if(strtolower($title) == 'browse') echo 'active';?>" href="<?= site_url('browse')?>">
                        Browse
                    </a>
                </li>
                <li class="nav-item">
                    <?php if ($this->session->userdata('username')): ?>
                    <a class="nav-link <?php if (in_array(strtolower($title), ['book a repair', 'my appointments'])) echo 'active';?>" href="<?= site_url('booking')?>">
                        Book
                    </a>
                    <?php else: ?>
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#bookRepairModal">
                        Book
                    </a>
                    <?php endif; ?>
                </li>
            </ul>
            <form class="d-flex flex-grow-1 flex-lg-grow-0 navbar-search me-lg-4" role="search">
                <input aria-label="Search" class="form-control me-2" placeholder="search" type="search">
                    <button class="btn btn-outline-primary" type="submit">
                        Search
                    </button>
                </input>
            </form>
            <div class="d-flex align-items-center gap-2 ms-lg-2">
                <?php
                $cartCount = 0;
                foreach (cart_items() as $_item) {
                    $cartCount += (int) $_item['quantity'];
                }
            ?>
            <a href="<?= site_url('cart') ?>" class="nav-link position-relative px-2 me-3" aria-label="Shopping cart">
                <i class="bi bi-cart fs-5"></i>
                <span id="cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?= $cartCount > 0 ? '' : 'd-none' ?>"><?= $cartCount ?></span>
            </a>
                <?php if ($this->session->userdata('username')): ?>
                <div class="dropdown">
                    <button class="btn btn-link nav-link dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5 me-1"></i>
                        <?= html_escape($this->session->userdata('username')) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="<?= site_url('orders') ?>">My Orders</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('booking/appointments') ?>">My Appointments</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('settings') ?>">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button type="button" class="btn btn-outline-danger btn-sm border-0 w-100 text-start rounded-0" data-bs-toggle="modal" data-bs-target="#signOutModal">Sign Out</button>
                        </li>
                    </ul>
                </div>
                <?php else: ?>
                <a href="<?= site_url('auth/signin') ?>" class="btn btn-primary">Sign In</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>