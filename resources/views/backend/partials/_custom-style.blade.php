<style>
    .textTransform {
        animation: colorChange 40s infinite;
    }

    @keyframes colorChange {
        0% {
            color: #ff0000;
        }

        25% {
            color: #00ff00;
        }

        50% {
            color: #0000ff;
        }

        75% {
            color: #ff00ff;
        }

        100% {
            color: #ff0000;
        }
    }
</style>

{{-- Global Page Header & Breadcrumb Styles --}}
<style>
    /* ───── Page Header Entrance Animation ───── */
    @keyframes pageHeaderFadeIn {
        0% {
            opacity: 0;
            transform: translateY(-12px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes titleSlideUp {
        0% {
            opacity: 0;
            transform: translateY(10px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes subtitleFadeIn {
        0% {
            opacity: 0;
            transform: translateY(6px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes breadcrumbSlideIn {
        0% {
            opacity: 0;
            transform: translateX(12px);
        }
        100% {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes breadcrumbItemFadeIn {
        0% {
            opacity: 0;
            transform: translateX(-6px);
        }
        100% {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* ───── Page Header ───── */
    .page-header {
        padding: 1.25rem 0 1rem !important;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
        animation: pageHeaderFadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .page-header .page-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: -0.025em;
        line-height: 1.2;
        animation: titleSlideUp 0.45s cubic-bezier(0.16, 1, 0.3, 1) 0.05s both;
    }

    .page-header .fs-13,
    .page-header p.text-muted {
        font-size: 0.8125rem;
        animation: subtitleFadeIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.12s both;
    }

    /* ───── Breadcrumb ───── */
    .page-header .breadcrumb {
        background: transparent;
        padding: 0;
        animation: breadcrumbSlideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) 0.1s both;
    }

    .page-header .breadcrumb-item {
        font-size: 0.8125rem;
        font-weight: 500;
        color: #94a3b8;
    }

    .page-header .breadcrumb-item:nth-child(1) {
        animation: breadcrumbItemFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) 0.15s both;
    }
    .page-header .breadcrumb-item:nth-child(2) {
        animation: breadcrumbItemFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) 0.22s both;
    }
    .page-header .breadcrumb-item:nth-child(3) {
        animation: breadcrumbItemFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) 0.29s both;
    }
    .page-header .breadcrumb-item:nth-child(4) {
        animation: breadcrumbItemFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) 0.36s both;
    }

    .page-header .breadcrumb-item a {
        color: #64748b;
        text-decoration: none;
        transition: color 0.2s ease;
        position: relative;
    }

    .page-header .breadcrumb-item a::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 0;
        height: 1.5px;
        background: currentColor;
        transition: width 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        pointer-events: none;
    }

    .page-header .breadcrumb-item a:hover {
        color: #1d4ed8;
    }

    .page-header .breadcrumb-item a:hover::after {
        width: 100%;
    }

    .page-header .breadcrumb-item.active {
        color: #0f172a;
        font-weight: 600;
    }

    .page-header .breadcrumb-item + .breadcrumb-item::before {
        content: "/";
        color: #cbd5e1;
        font-weight: 400;
        padding: 0 6px;
    }

    /* ───── Reduced Motion ───── */
    @media (prefers-reduced-motion: reduce) {
        .page-header,
        .page-header .page-title,
        .page-header p.text-muted,
        .page-header .breadcrumb,
        .page-header .breadcrumb-item {
            animation: none !important;
        }
    }
</style>