@extends('backend.app')

@section('content')
    <!--app-content open-->
    <div class="app-content main-content mt-0">
        <div class="side-app" style="margin-bottom: 60px">
            <!-- CONTAINER -->
            <div class="main-container container-fluid">

                <!-- PAGE-HEADER -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Dashboard</h1>
                    </div>
                    <div class="ms-auto pageheader-btn">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </div>
                </div>
                <!-- PAGE-HEADER END -->

                <!-- ROW-1: Main Statistics Cards -->
                <div class="row">
                    <!-- Total Users Card -->
                    <div class="col-lg-6 col-sm-12 col-md-6 col-xl-3">
                        <div class="card overflow-hidden">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h3 class="mb-2 fw-semibold">{{ number_format($userStats['total']) }}</h3>
                                        <p class="text-muted fs-13 mb-0">Total Users</p>
                                        <p class="text-muted mb-0 mt-2 fs-12">
                                            <span
                                                class="icn-box {{ $userStats['growth_percentage'] >= 0 ? 'text-success' : 'text-danger' }} fw-semibold fs-13 me-1">
                                                <i
                                                    class='fa fa-long-arrow-{{ $userStats['growth_percentage'] >= 0 ? 'up' : 'down' }}'></i>
                                                {{ abs($userStats['growth_percentage']) }}%
                                            </span>
                                            since last month
                                        </p>
                                    </div>
                                    <div class="col col-auto top-icn dash">
                                        <div class="counter-icon bg-primary dash ms-auto box-shadow-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="fill-white"
                                                enable-background="new 0 0 24 24" viewBox="0 0 24 24">
                                                <path
                                                    d="M12,8c-2.2091675,0-4,1.7908325-4,4s1.7908325,4,4,4c2.208252-0.0021973,3.9978027-1.791748,4-4C16,9.7908325,14.2091675,8,12,8z M12,15c-1.6568604,0-3-1.3431396-3-3s1.3431396-3,3-3c1.6561279,0.0018311,2.9981689,1.3438721,3,3C15,13.6568604,13.6568604,15,12,15z M21.960022,11.8046875C19.9189453,6.9902344,16.1025391,4,12,4s-7.9189453,2.9902344-9.960022,7.8046875c-0.0537109,0.1246948-0.0537109,0.2659302,0,0.390625C4.0810547,17.0097656,7.8974609,20,12,20s7.9190063-2.9902344,9.960022-7.8046875C22.0137329,12.0706177,22.0137329,11.9293823,21.960022,11.8046875z M12,19c-3.6396484,0-7.0556641-2.6767578-8.9550781-7C4.9443359,7.6767578,8.3603516,5,12,5s7.0556641,2.6767578,8.9550781,7C19.0556641,16.3232422,15.6396484,19,12,19z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Camps Card -->
                    <div class="col-lg-6 col-sm-12 col-md-6 col-xl-3">
                        <div class="card overflow-hidden">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h3 class="mb-2 fw-semibold">{{ number_format($campStats['total']) }}</h3>
                                        <p class="text-muted fs-13 mb-0">Total Camps</p>
                                        <p class="text-muted mb-0 mt-2 fs-12">
                                            <span
                                                class="icn-box {{ $campStats['growth_percentage'] >= 0 ? 'text-success' : 'text-danger' }} fw-semibold fs-13 me-1">
                                                <i
                                                    class='fa fa-long-arrow-{{ $campStats['growth_percentage'] >= 0 ? 'up' : 'down' }}'></i>
                                                {{ abs($campStats['growth_percentage']) }}%
                                            </span>
                                            since last month
                                        </p>
                                    </div>
                                    <div class="col col-auto top-icn dash">
                                        <div class="counter-icon bg-secondary dash ms-auto box-shadow-secondary">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="fill-white"
                                                enable-background="new 0 0 24 24" viewBox="0 0 24 24">
                                                <path
                                                    d="M19.5,7H16V5.9169922c0-2.2091064-1.7908325-4-4-4s-4,1.7908936-4,4V7H4.5C4.4998169,7,4.4996338,7,4.4993896,7C4.2234497,7.0001831,3.9998169,7.223999,4,7.5V19c0.0018311,1.6561279,1.3438721,2.9981689,3,3h10c1.6561279-0.0018311,2.9981689-1.3438721,3-3V7.5c0-0.0001831,0-0.0003662,0-0.0006104C19.9998169,7.2234497,19.776001,6.9998169,19.5,7z M9,5.9169922c0-1.6568604,1.3431396-3,3-3s3,1.3431396,3,3V7H9V5.9169922z M19,19c-0.0014038,1.1040039-0.8959961,1.9985962-2,2H7c-1.1040039-0.0014038-1.9985962-0.8959961-2-2V8h3v2.5C8,10.776123,8.223877,11,8.5,11S9,10.776123,9,10.5V8h6v2.5c0,0.0001831,0,0.0003662,0,0.0005493C15.0001831,10.7765503,15.223999,11.0001831,15.5,11c0.0001831,0,0.0003662,0,0.0006104,0C15.7765503,10.9998169,16.0001831,10.776001,16,10.5V8h3V19z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Game Slots Card -->
                    <div class="col-lg-6 col-sm-12 col-md-6 col-xl-3">
                        <div class="card overflow-hidden">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h3 class="mb-2 fw-semibold">{{ number_format($gameSlotStats['total']) }}</h3>
                                        <p class="text-muted fs-13 mb-0">Game Slots</p>
                                        <p class="text-muted mb-0 mt-2 fs-12">
                                            <span
                                                class="icn-box {{ $gameSlotStats['growth_percentage'] >= 0 ? 'text-success' : 'text-danger' }} fw-semibold fs-13 me-1">
                                                <i
                                                    class='fa fa-long-arrow-{{ $gameSlotStats['growth_percentage'] >= 0 ? 'up' : 'down' }}'></i>
                                                {{ abs($gameSlotStats['growth_percentage']) }}%
                                            </span>
                                            since last week
                                        </p>
                                    </div>
                                    <div class="col col-auto top-icn dash">
                                        <div class="counter-icon bg-info dash ms-auto box-shadow-info">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="fill-white"
                                                enable-background="new 0 0 24 24" viewBox="0 0 24 24">
                                                <path
                                                    d="M7.5,12C7.223877,12,7,12.223877,7,12.5v5.0005493C7.0001831,17.7765503,7.223999,18.0001831,7.5,18h0.0006104C7.7765503,17.9998169,8.0001831,17.776001,8,17.5v-5C8,12.223877,7.776123,12,7.5,12z M19,2H5C3.3438721,2.0018311,2.0018311,3.3438721,2,5v14c0.0018311,1.6561279,1.3438721,2.9981689,3,3h14c1.6561279-0.0018311,2.9981689-1.3438721,3-3V5C21.9981689,3.3438721,20.6561279,2.0018311,19,2z M21,19c-0.0014038,1.1040039-0.8959961,1.9985962-2,2H5c-1.1040039-0.0014038-1.9985962-0.8959961-2-2V5c0.0014038-1.1040039,0.8959961-1.9985962,2-2h14c1.1040039,0.0014038,1.9985962,0.8959961,2,2V19z M12,6c-0.276123,0-0.5,0.223877-0.5,0.5v11.0005493C11.5001831,17.7765503,11.723999,18.0001831,12,18h0.0006104c0.2759399-0.0001831,0.4995728-0.223999,0.4993896-0.5v-11C12.5,6.223877,12.276123,6,12,6z M16.5,10c-0.276123,0-0.5,0.223877-0.5,0.5v7.0005493C16.0001831,17.7765503,16.223999,18.0001831,16.5,18h0.0006104C16.7765503,17.9998169,17.0001831,17.776001,17,17.5v-7C17,10.223877,16.776123,10,16.5,10z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Card -->
                    <div class="col-lg-6 col-sm-12 col-md-6 col-xl-3">
                        <div class="card overflow-hidden">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h3 class="mb-2 fw-semibold">${{ number_format($revenue['this_month'], 2) }}</h3>
                                        <p class="text-muted fs-13 mb-0">Monthly Revenue</p>
                                        <p class="text-muted mb-0 mt-2 fs-12">
                                            <span
                                                class="icn-box {{ $revenue['growth_percentage'] >= 0 ? 'text-success' : 'text-danger' }} fw-semibold fs-13 me-1">
                                                <i
                                                    class='fa fa-long-arrow-{{ $revenue['growth_percentage'] >= 0 ? 'up' : 'down' }}'></i>
                                                {{ abs($revenue['growth_percentage']) }}%
                                            </span>
                                            since last month
                                        </p>
                                    </div>
                                    <div class="col col-auto top-icn dash">
                                        <div class="counter-icon bg-warning dash ms-auto box-shadow-warning">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="fill-white"
                                                enable-background="new 0 0 24 24" viewBox="0 0 24 24">
                                                <path
                                                    d="M9,10h2.5c0.276123,0,0.5-0.223877,0.5-0.5S11.776123,9,11.5,9H10V8c0-0.276123-0.223877-0.5-0.5-0.5S9,7.723877,9,8v1c-1.1045532,0-2,0.8954468-2,2s0.8954468,2,2,2h1c0.5523071,0,1,0.4476929,1,1s-0.4476929,1-1,1H7.5C7.223877,15,7,15.223877,7,15.5S7.223877,16,7.5,16H9v1.0005493C9.0001831,17.2765503,9.223999,17.5001831,9.5,17.5h0.0006104C9.7765503,17.4998169,10.0001831,17.276001,10,17v-1c1.1045532,0,2-0.8954468,2-2s-0.8954468-2-2-2H9c-0.5523071,0-1-0.4476929-1-1S8.4476929,10,9,10z M21.5,12H17V2.5c0.000061-0.0875244-0.0228882-0.1735229-0.0665283-0.2493896c-0.1375732-0.2393188-0.4431152-0.3217773-0.6824951-0.1842041l-3.2460327,1.8603516L9.7481079,2.0654297c-0.1536865-0.0878906-0.3424072-0.0878906-0.4960938,0l-3.256897,1.8613281L2.7490234,2.0664062C2.6731567,2.0227661,2.5871582,1.9998779,2.4996338,1.9998779C2.2235718,2.000061,1.9998779,2.223938,2,2.5v17c0.0012817,1.380188,1.119812,2.4987183,2.5,2.5H19c1.6561279-0.0018311,2.9981689-1.3438721,3-3v-6.5006104C21.9998169,12.2234497,21.776001,11.9998169,21.5,12z M4.5,21c-0.828064-0.0009155-1.4990845-0.671936-1.5-1.5V3.3623047l2.7412109,1.5712891c0.1575928,0.0872192,0.348877,0.0875854,0.5068359,0.0009766L9.5,3.0761719l3.2519531,1.8583984c0.157959,0.0866089,0.3492432,0.0862427,0.5068359-0.0009766L16,3.3623047V19c0.0008545,0.7719116,0.3010864,1.4684448,0.7803345,2H4.5z M21,19c0,1.1045532-0.8954468,2-2,2s-2-0.8954468-2-2v-6h4V19z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ROW-1 END-->

                <!-- ROW-2 -->
                <div class="row">
                    <!-- Revenue By Channel -->
                    <div class="col-sm-12 col-md-12 col-xl-4 col-lg-6">
                        <div class="row">
                            <div class="col-lg-12 col-xl-12 col-md-6 col-sm-12">
                                <div class="card">
                                    <div class="card-body pb-2">
                                        <div class="title-head mb-3">
                                            <h3 class="mb-5 card-title">Camp Status Distribution</h3>
                                            <div class="storage-percent">
                                                <div class="progress fileprogress h-auto ps-0 shadow1">
                                                    @php
                                                        $total =
                                                            $campStats['upcoming'] +
                                                            $campStats['ongoing'] +
                                                            $campStats['completed'];
                                                        $upcomingPercent =
                                                            $total > 0 ? ($campStats['upcoming'] / $total) * 100 : 0;
                                                        $ongoingPercent =
                                                            $total > 0 ? ($campStats['ongoing'] / $total) * 100 : 0;
                                                        $completedPercent =
                                                            $total > 0 ? ($campStats['completed'] / $total) * 100 : 0;
                                                    @endphp
                                                    <span class="progress-bar progress-bar-xs bg-primary" role="progressbar"
                                                        style="width: {{ $upcomingPercent }}%"></span>
                                                    <span class="progress-bar progress-bar-xs bg-success" role="progressbar"
                                                        style="width: {{ $ongoingPercent }}%"></span>
                                                    <span class="progress-bar progress-bar-xs bg-secondary"
                                                        role="progressbar" style="width: {{ $completedPercent }}%"></span>
                                                </div>
                                                <div class="remaining-storage">
                                                    <div class="text-muted fs-13 mb-1 mt-3">Total Camps Created</div>
                                                    <div class="fw-semibold fs-14 mb-1 mt-3">
                                                        {{ number_format($campStats['total']) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="content-main mt-5">
                                            <ul class="task-list1 row mx-auto">
                                                <li class="col-xl-6">
                                                    <span class="mb-0 fs-13 me-1"><i
                                                            class="task-icon1 bg-primary me-3"></i>Upcoming</span>
                                                    <span class="text-primary fw-semibold fs-12">
                                                        <span class="">({{ $campStats['upcoming'] }})</span>
                                                    </span>
                                                </li>
                                                <li class="col-xl-6">
                                                    <span class="mb-0 fs-13 me-1"><i
                                                            class="task-icon1 bg-success"></i>Ongoing</span>
                                                    <span class="text-success fw-semibold fs-12">
                                                        <span class="">({{ $campStats['ongoing'] }})</span>
                                                    </span>
                                                </li>
                                                <li class="col-xl-6">
                                                    <span class="mb-0 fs-13 me-1"><i
                                                            class="task-icon1 bg-secondary"></i>Completed</span>
                                                    <span class="text-secondary fw-semibold fs-12">
                                                        <span class="">({{ $campStats['completed'] }})</span>
                                                    </span>
                                                </li>
                                                <li class="col-xl-6 mb-xl-0">
                                                    <span class="mb-0 fs-13 me-1"><i
                                                            class="task-icon1 bg-info"></i>Active</span>
                                                    <span class="text-info fw-semibold fs-12">
                                                        <span class="">({{ $campStats['active'] }})</span>
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Game Slot Status -->
                            <div class="col-xl-12 col-lg-12 col-md-6 col-sm-12">
                                <div class="card" style="height: 270px; overflow-x: auto">
                                    <div class="card-header border-bottom">
                                        <h4 class="card-title fw-semibold">Game Slot Status</h4>
                                    </div>
                                    <div class="card-body p-0 customers mt-1">
                                        <div class="list-group py-1">
                                            <a href="javascript:void(0);" class="border-0">
                                                <div class="list-group-item border-0">
                                                    <div class="media mt-0 align-items-center">
                                                        <div class="transaction-icon">
                                                            <i class="fe fe-check text-success"></i>
                                                        </div>
                                                        <div class="media-body">
                                                            <div class="d-flex align-items-center">
                                                                <div class="mt-0">
                                                                    <h5 class="mb-1 fs-13 fw-normal text-dark">Available
                                                                        Slots</h5>
                                                                    <p class="mb-0 fs-12 text-muted">Ready to be assigned
                                                                    </p>
                                                                </div>
                                                                <span class="ms-auto fs-13">
                                                                    <span
                                                                        class="float-end text-dark fw-semibold">{{ $gameSlotStats['available'] }}
                                                                    </span>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                            <a href="javascript:void(0);" class="border-0">
                                                <div class="list-group-item border-0">
                                                    <div class="media mt-0 align-items-center">
                                                        <div class="transaction-icon">
                                                            <i class="fe fe-clock text-warning"></i>
                                                        </div>
                                                        <div class="media-body">
                                                            <div class="d-flex align-items-center">
                                                                <div class="mt-0">
                                                                    <h5 class="mb-1 fs-13 fw-normal text-dark">Assigned
                                                                        Slots</h5>
                                                                    <p class="mb-0 fs-12 text-muted">Currently in use</p>
                                                                </div>
                                                                <span class="ms-auto fs-13">
                                                                    <span
                                                                        class="float-end text-dark fw-semibold">{{ $gameSlotStats['assigned'] }}</span>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                            <a href="javascript:void(0);" class="border-0">
                                                <div class="list-group-item border-0">
                                                    <div class="media mt-0 align-items-center">
                                                        <div class="transaction-icon">
                                                            <i class="fe fe-check-circle text-info"></i>
                                                        </div>
                                                        <div class="media-body">
                                                            <div class="d-flex align-items-center">
                                                                <div class="mt-0">
                                                                    <h5 class="mb-1 fs-13 fw-normal text-dark">Completed
                                                                        Slots</h5>
                                                                    <p class="mb-0 fs-12 text-muted">Finished games</p>
                                                                </div>
                                                                <span class="ms-auto fs-13">
                                                                    <span
                                                                        class="float-end text-dark fw-semibold">{{ $gameSlotStats['completed'] }}</span>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                            <a href="javascript:void(0);" class="border-0">
                                                <div class="list-group-item border-0">
                                                    <div class="media mt-0 align-items-center">
                                                        <div class="transaction-icon">
                                                            <i class="fe fe-x text-danger"></i>
                                                        </div>
                                                        <div class="media-body">
                                                            <div class="d-flex align-items-center">
                                                                <div class="mt-0">
                                                                    <h5 class="mb-1 fs-13 fw-normal text-dark">Blocked
                                                                        Slots</h5>
                                                                    <p class="mb-0 fs-12 text-muted">Not available</p>
                                                                </div>
                                                                <span class="ms-auto fs-13">
                                                                    <span
                                                                        class="float-end text-dark fw-semibold">{{ $gameSlotStats['blocked'] }}</span>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Camps Chart -->
                    <div class="col-sm-12 col-md-12 col-lg-6 col-xl-8">
                        <div class="card" style="height: min-content">
                            <div class="card-header border-bottom">
                                <h3 class="card-title">Monthly Camps Trend</h3>
                                <div class="ms-auto">
                                    <div class="btn-group p-0 ms-auto">
                                        <button class="btn btn-primary-light btn-sm"
                                            type="button">{{ date('Y') }}</button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="sales-stats d-flex">
                                    <div>
                                        <div class="text-muted fs-13">Total Camps This Year
                                            <span
                                                class="p-2 br-5 {{ $campStats['growth_percentage'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                <i
                                                    class="fe fe-arrow-{{ $campStats['growth_percentage'] >= 0 ? 'up' : 'down' }}-right"></i>
                                            </span>
                                        </div>
                                        <h3 class="fw-semibold">{{ number_format($campStats['total']) }}</h3>
                                        <div>
                                            <span
                                                class="{{ $campStats['growth_percentage'] >= 0 ? 'text-success' : 'text-danger' }} fs-13 me-1">
                                                {{ abs($campStats['growth_percentage']) }}%
                                            </span>
                                            {{ $campStats['growth_percentage'] >= 0 ? 'Increase' : 'Decrease' }} Since Last
                                            Month
                                        </div>
                                    </div>
                                </div>
                                <div style="position: relative; height: 300px; margin-top: 20px;">
                                    <canvas id="monthlyCampsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ROW-2 END -->

                <!-- ROW-3 -->
                <div class="row">
                    <!-- Daily Activity -->
                    {{-- <div class="col-xl-4 col-md-12">
                        <div class="card" style="height: min-content">
                            <div class="card-header border-bottom">
                                <h4 class="card-title fw-semibold">Recent Activity</h4>
                            </div>
                            <div class="card-body pb-0">
                                <ul class="task-list">
                                    @forelse($dailyActivities as $activity)
                                        <li>
                                            <i class="task-icon bg-{{ $activity['color'] }}"></i>
                                            <p class="fw-semibold mb-1 fs-13">
                                                {{ $activity['title'] }}
                                                <span
                                                    class="text-muted fs-12 ms-2 ms-auto float-end">{{ $activity['time'] }}</span>
                                            </p>
                                            <p class="text-muted fs-12">{{ $activity['description'] }}</p>
                                        </li>
                                    @empty
                                        <li>
                                            <p class="text-muted text-center">No recent activities</p>
                                        </li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div> --}}

                    <!-- Recent Camps Table -->
                    <div class="col-xl-12 col-md-12">
                        <div class="card">
                            <div class="card-header border-bottom">
                                <h4 class="card-title fw-semibold">Recent Camps</h4>
                                <a href="{{ route('admin.camps.index') }}" class="ms-auto">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table text-nowrap mb-0">
                                        <thead>
                                            <tr>
                                                <th class="border-bottom-0">Camp Name</th>
                                                <th class="border-bottom-0">Sport</th>
                                                <th class="border-bottom-0">Location</th>
                                                <th class="border-bottom-0">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($recentCamps as $camp)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div>
                                                                <h6 class="mb-0 fs-14">{{ $camp->camp_name }}</h6>
                                                                <small
                                                                    class="text-muted">{{ \Carbon\Carbon::parse($camp->created_at)->format('M d, Y') }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="fs-13">{{ $camp->sports_type_name ?? 'N/A' }}</td>
                                                    <td class="fs-13">{{ Str::limit($camp->location, 20) }}</td>
                                                    <td>
                                                        <span
                                                            class="badge bg-{{ $camp->status == 'active' ? 'success' : 'secondary' }}">
                                                            {{ ucfirst($camp->status) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">No recent camps</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ROW-3 END -->
            </div>
        </div>
    </div>
    <!-- CONTAINER CLOSED -->
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Monthly Camps Line Chart
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const monthlyCampsData = Array(12).fill(0);
        @foreach ($campStats['monthly'] as $month)
            monthlyCampsData[{{ $month->month - 1 }}] = {{ $month->count }};
        @endforeach

        const monthlyCampsCtx = document.getElementById('monthlyCampsChart').getContext('2d');
        new Chart(monthlyCampsCtx, {
            type: 'line',
            data: {
                labels: monthNames,
                datasets: [{
                    label: 'Camps Created',
                    data: monthlyCampsData,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#0d6efd',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Sports Types Bar Chart
        const sportsTypeCtx = document.getElementById('sportsTypeChart').getContext('2d');
        new Chart(sportsTypeCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($sportsStats['camps_by_sport']->pluck('sports_type_name')) !!},
                datasets: [{
                    label: 'Camps',
                    data: {!! json_encode($sportsStats['camps_by_sport']->pluck('total')) !!},
                    backgroundColor: [
                        '#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545',
                        '#fd7e14', '#ffc107', '#198754', '#20c997', '#0dcaf0'
                    ],
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
@endpush

@push('styles')
    <style>
        .counter-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .counter-icon svg {
            width: 30px;
            height: 30px;
        }

        .box-shadow-primary {
            box-shadow: 0 0.5rem 1rem rgba(13, 110, 253, 0.15);
        }

        .box-shadow-secondary {
            box-shadow: 0 0.5rem 1rem rgba(108, 117, 125, 0.15);
        }

        .box-shadow-info {
            box-shadow: 0 0.5rem 1rem rgba(13, 202, 240, 0.15);
        }

        .box-shadow-warning {
            box-shadow: 0 0.5rem 1rem rgba(255, 193, 7, 0.15);
        }

        .card {
            border-radius: 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }

        .transaction-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .bg-success-transparent {
            background-color: rgba(25, 135, 84, 0.1);
        }

        .bg-warning-transparent {
            background-color: rgba(255, 193, 7, 0.1);
        }

        .bg-info-transparent {
            background-color: rgba(13, 202, 240, 0.1);
        }

        .bg-danger-transparent {
            background-color: rgba(220, 53, 69, 0.1);
        }

        .bg-primary-transparent {
            background-color: rgba(13, 110, 253, 0.1);
        }

        .bg-secondary-transparent {
            background-color: rgba(108, 117, 125, 0.1);
        }

        .task-icon1 {
            width: 10px;
            height: 10px;
            display: inline-block;
            border-radius: 50%;
        }

        .task-icon {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
        }

        /* Chart Container Fixes */
        canvas {
            max-width: 100%;
            height: auto !important;
        }

        .chart-container {
            position: relative;
            width: 100%;
        }
    </style>
@endpush
