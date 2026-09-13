<!--APP-SIDEBAR-->
<div class="sticky">
    <div class="app-sidebar__overlay" data-bs-toggle="sidebar"></div>
    <div class="app-sidebar" style="overflow: scroll">
        <div class="side-header">
            <a class="header-brand1" href="<?php echo e(route('admin.dashboard')); ?>">
                <img src="<?php echo e(asset($settings->logo ?? 'default/logo-mini.png')); ?>" class="header-brand-img desktop-logo"
                    alt="logo">
                <img src="<?php echo e(asset($settings->logo ?? 'default/logo-mini.png')); ?>" class="header-brand-img toggle-logo"
                    alt="logo">
                <img src="<?php echo e(asset($settings->logo ?? 'default/logo-mini.png')); ?>" class="header-brand-img light-logo"
                    alt="logo">
                <img src="<?php echo e(asset($settings->logo ?? 'default/logo.png')); ?>" class="header-brand-img light-logo1"
                    alt="logo">
            </a>
        </div>
        <div class="main-sidemenu">
            <div class="slide-left disabled" id="slide-left"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191"
                    width="24" height="24" viewBox="0 0 24 24">
                    <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z" />
                </svg>
            </div>

            <ul class="side-menu mt-2">
                
                <li class="slide">
                    <a class="side-menu__item <?php echo e(request()->routeIs('admin.dashboard') ? 'has-link' : ''); ?>"
                        href="<?php echo e(route('admin.dashboard')); ?>">
                        <i class="fa-solid fa-gauge-high"></i>
                        <span class="side-menu__label">Dashboard</span>
                    </a>
                </li>

                
                <li class="slide">
                    <a class="side-menu__item <?php echo e(request()->routeIs('admin.sports-type.*') ? 'has-link' : ''); ?>"
                        href="<?php echo e(route('admin.sports-type.index')); ?>">
                        <i class="fa-solid fa-baseball-bat-ball"></i>
                        <span class="side-menu__label">Sports Type</span>
                    </a>
                </li>

                
                <li class="slide">
                    <a class="side-menu__item <?php echo e(request()->routeIs('admin.users.manage.*') ? 'has-link' : ''); ?>"
                        href="<?php echo e(route('admin.users.manage.index')); ?>">
                        <i class="fa-solid fa-users"></i>
                        <span class="side-menu__label">User List</span>
                    </a>
                </li>

                
                <li class="slide">
                    <a class="side-menu__item <?php echo e(request()->routeIs('admin.camps.*') ? 'has-link' : ''); ?>"
                        href="<?php echo e(route('admin.camps.index')); ?>">
                        <i class="fa-solid fa-tent"></i>
                        <span class="side-menu__label">Manage Camps</span>
                    </a>
                </li>

                
                <li class="slide">
                    <a class="side-menu__item <?php echo e(request()->routeIs('admin.coupon.*') ? 'has-link' : ''); ?>"
                        href="<?php echo e(route('admin.coupon.index')); ?>">
                        <i class="fa-solid fa-tag"></i>
                        <span class="side-menu__label">Coupons</span>
                    </a>
                </li>

                
                <li class="slide">
                    <a class="side-menu__item <?php echo e(request()->routeIs('admin.monitor.*') ? 'has-link' : ''); ?>"
                        href="<?php echo e(route('admin.monitor.index')); ?>">
                        <i class="fa-solid fa-chart-line"></i>
                        <span class="side-menu__label">Payment Monitor</span>
                    </a>
                </li>

                
                <li class="slide">
                    <a class="side-menu__item <?php echo e(request()->routeIs('admin.cms.privecyandterms.*') ? 'has-link' : ''); ?>"
                        data-bs-toggle="slide" href="#">
                        <i class="fa-solid fa-file-contract"></i>
                        <span class="side-menu__label">Terms & Privacy</span>
                        <i class="angle fa fa-angle-right"></i>
                    </a>

                    <ul class="slide-menu">
                        <li><a href="<?php echo e(route('admin.cms.privecyandterms.terms')); ?>" class="slide-item">Terms &
                                Condition</a></li>
                        <li><a href="<?php echo e(route('admin.cms.privecyandterms.privacy')); ?>" class="slide-item">Privacy
                                Policy</a></li>
                    </ul>
                </li>

                <hr>

                
                <li class="slide">
                    <a class="side-menu__item <?php echo e(request()->routeIs('admin.cms.home*') ? 'has-link' : ''); ?>"
                        data-bs-toggle="slide" href="#">
                        <i class="fa-solid fa-home"></i>
                        <span class="side-menu__label">Home Page</span>
                        <i class="angle fa fa-angle-right"></i>
                    </a>

                    <ul class="slide-menu">
                        <li><a href="<?php echo e(route('admin.cms.home.hero.section')); ?>" class="slide-item">Hero Section</a>
                        </li>
                        <li><a href="<?php echo e(route('admin.cms.home.training-camp.section')); ?>" class="slide-item">Training
                                Camps Section</a></li>
                        <li><a href="<?php echo e(route('admin.cms.home.slider.index')); ?>" class="slide-item">Partners
                                Section</a></li>
                        <li><a href="<?php echo e(route('admin.cms.home.features.index')); ?>" class="slide-item">Features
                                Section</a></li>
                        <li><a href="<?php echo e(route('admin.cms.home.operation.section')); ?>" class="slide-item">Operations
                                Section</a></li>
                        <li><a href="<?php echo e(route('admin.cms.home.testimonial.index')); ?>" class="slide-item">Testimonial
                                Section</a></li>
                    </ul>
                </li>

                
                <li class="slide">
                    <a class="side-menu__item <?php echo e(request()->routeIs('admin.cms.about*') ? 'has-link' : ''); ?>"
                        data-bs-toggle="slide" href="#">
                        <i class="fa-solid fa-address-card"></i>
                        <span class="side-menu__label">About Page</span>
                        <i class="angle fa fa-angle-right"></i>
                    </a>

                    <ul class="slide-menu">
                        <li><a href="<?php echo e(route('admin.cms.about.index')); ?>" class="slide-item">About Us</a>
                        </li>
                        <li><a href="<?php echo e(route('admin.cms.about.team.index')); ?>" class="slide-item">Our Team</a></li>
                        <li><a href="<?php echo e(route('admin.cms.about.getting-started.index')); ?>" class="slide-item">Get
                                Started</a></li>
                    </ul>
                </li>

                <hr>

                
                <li class="slide">
                    <a class="side-menu__item <?php echo e(request()->routeIs('admin.setting.*') ? 'has-link' : ''); ?>"
                        data-bs-toggle="slide" href="#">
                        <i class="fa-solid fa-gear"></i>
                        <span class="side-menu__label">Settings</span><i class="angle fa fa-angle-right"></i>
                    </a>
                    <ul class="slide-menu">
                        <li><a href="<?php echo e(route('admin.setting.general.index')); ?>" class="slide-item">General
                                Settings</a></li>
                        <li><a href="<?php echo e(route('admin.setting.logo.index')); ?>" class="slide-item">Logo Settings</a>
                        </li>
                        <li><a href="<?php echo e(route('admin.setting.profile.index')); ?>" class="slide-item">Profile
                                Settings</a></li>
                        <li><a href="<?php echo e(route('admin.setting.mail.index')); ?>" class="slide-item">Mail Settings</a>
                        </li>
                        <li><a href="<?php echo e(route('admin.setting.stripe.index')); ?>" class="slide-item">Stripe
                                Settings</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
<!--/APP-SIDEBAR-->

<script>
    // Auto-expand parent slide menus when a sub-item is already marked active by Blade
    (function() {
        'use strict';

        function expandActiveParents() {
            document.querySelectorAll('.slide-menu .slide-item.active').forEach(function(item) {
                var parentUl = item.closest('.slide-menu');
                var parentLi = item.closest('.slide');
                if (parentLi && !parentLi.classList.contains('is-expanded')) {
                    parentUl.classList.add('open');
                    parentUl.style.display = '';
                    parentLi.classList.add('is-expanded');
                    var toggle = parentLi.querySelector('[data-bs-toggle="slide"]');
                    if (toggle) toggle.setAttribute('aria-expanded', 'true');
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', expandActiveParents);
        } else {
            expandActiveParents();
        }
        setTimeout(expandActiveParents, 300);
    })();
</script>

<style>
    .side-menu {
        padding-top: 10px;
    }

    /* Section Divider */
    .side-menu hr {
        margin: 16px 20px;
        border: 0;
        height: 1px;
        background: linear-gradient(to right, transparent, #E5E7EB, transparent);
        opacity: 0.6;
    }

    /* Base Menu Item (Slim) */
    .side-menu__item {
        display: flex;
        align-items: center;
        padding: 12px 10px;
        color: #4B5563;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 8px;
        margin: 2px 12px;
        border: 1px solid transparent;
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }

    /* Hover effect */
    .side-menu__item:hover {
        background: linear-gradient(118deg, rgba(0, 174, 239, 0.08), rgba(0, 174, 239, 0.02));
        color: #00AEEF;
        transform: translateX(3px);
    }

    /* Active / expanded link */
    .side-menu__item.has-link,
    .side-menu__item[aria-expanded="true"] {
        background: linear-gradient(118deg, rgba(0, 174, 239, 0.12), rgba(0, 174, 239, 0.03));
        color: #00AEEF;
        font-weight: 600;
        border-color: rgba(0, 174, 239, 0.2);
        box-shadow: 0 2px 6px 0 rgba(0, 174, 239, 0.1);
    }

    /* Active accent line */
    .side-menu__item.has-link::before {
        content: '';
        position: absolute;
        left: 0;
        top: 12%;
        height: 76%;
        width: 3px;
        background-color: #00AEEF;
        border-radius: 0 3px 3px 0;
    }

    /* Icons (FontAwesome & SVG) */
    .side-menu__item i,
    .side-menu__item svg {
        width: 20px;
        height: 20px;
        font-size: 15px;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
        color: #6B7280;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Active / hover icons */
    .side-menu__item:hover i,
    .side-menu__item:hover svg,
    .side-menu__item.has-link i,
    .side-menu__item.has-link svg,
    .side-menu__item[aria-expanded="true"] i,
    .side-menu__item[aria-expanded="true"] svg {
        color: #00AEEF;
        transform: scale(1.1);
    }

    /* Dropdown Arrow */
    .side-menu__item .angle {
        margin-left: auto;
        font-size: 13px;
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        color: #9CA3AF;
        width: auto;
        height: auto;
        margin-right: 0;
    }

    .side-menu__item[aria-expanded="true"] .angle,
    .side-menu__item.has-link .angle {
        color: #00AEEF;
        transform: rotate(90deg);
    }

    /* Submenu Container */
    .slide-menu {
        padding-left: 16px;
        margin: 2px 0;
        position: relative;
    }

    /* Submenu Item */
    .slide-menu .slide-item {
        display: flex;
        align-items: center;
        padding: 7px 14px 7px 28px;
        color: #6B7280;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 6px;
        margin: 1px 12px 1px 6px;
        position: relative;
    }

    /* Clean up any default theme injected icons or pseudo elements */
    .app-sidebar .side-menu .slide-menu .slide-item::after,
    .app-sidebar .side-menu .slide-menu .slide-item i {
        display: none !important;
        content: none !important;
    }

    /* Submenu circle indicator */
    .app-sidebar .side-menu .slide-menu .slide-item::before {
        content: '' !important;
        position: absolute !important;
        left: 12px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        width: 6px !important;
        height: 6px !important;
        background-color: #D1D5DB !important;
        background-image: none !important;
        border: none !important;
        border-radius: 50% !important;
        transition: all 0.2s ease !important;
        font-family: inherit !important;
        font-size: 0 !important;
    }

    /* Active circle - filled with brand color */
    .app-sidebar .side-menu .slide-menu .slide-item.active::before {
        background-color: #00AEEF !important;
        box-shadow: 0 0 0 3px rgba(0, 174, 239, 0.15) !important;
    }

    /* Hover circle - filled with brand color */
    .app-sidebar .side-menu .slide-menu .slide-item:hover::before {
        background-color: #00AEEF !important;
    }

    .app-sidebar .side-menu .slide-menu .slide-item:hover {
        background-color: rgba(0, 174, 239, 0.05);
        color: #00AEEF;
        transform: translateX(3px);
    }

    .slide-menu .slide-item.active {
        color: #00AEEF;
        font-weight: 600;
        background-color: rgba(0, 174, 239, 0.08);
    }
</style>
<?php /**PATH E:\remote-work\whistle-works-backend\resources\views/backend/partials/_sidebar.blade.php ENDPATH**/ ?>