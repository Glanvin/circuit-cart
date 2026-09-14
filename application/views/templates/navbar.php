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
                <li class="nav-item dropdown">
                    <a aria-expanded="false" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" id="categories" role="button">
                        Categories
                    </a>
                    <ul aria-labelledby="categories" class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="#">
                                Smartphones
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                Laptops
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                Tablets
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                Licenses
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
            <form class="d-flex flex-grow-1 flex-lg-grow-0 me-lg-3" role="search">
                <input aria-label="Search" class="form-control me-2" placeholder="search" type="search">
                    <button class="btn btn-outline-primary" type="submit">
                        Search
                    </button>
                </input>
            </form>
            <div class="d-flex align-items-center">
                <a href="#" class="nav-link position-relative px-2" aria-label="Shopping cart">
                    <i class="bi bi-cart fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"></span>
                </a>
                <div class="dropdown">
                    <button class="btn btn-link nav-link dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5 me-1"></i>
                        <?= html_escape($this->session->userdata('username')) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#">My Account</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('settings') ?>">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button type="button" class="btn btn-outline-danger btn-sm border-0 w-100 text-start rounded-0" data-bs-toggle="modal" data-bs-target="#signOutModal">Sign Out</button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>