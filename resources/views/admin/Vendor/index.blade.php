@extends('admin.layout.app')
@section('title', 'Vendors')

@section('content')
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Vendors</h4>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
                               @if (Auth::guard('admin')->check() ||
                                        ($sideMenuPermissions->has('Vendors') && $sideMenuPermissions['Vendors']->contains('create')))
                                    <a class="btn btn-primary mb-3 text-white"
                                        href="{{ url('/admin/vendor-create') }}">Create</a>
                                @endif

                                {{-- @if (Auth::guard('admin')->check() || ($sideMenuPermissions->has('users') && $sideMenuPermissions['users']->contains('view')))
                                    <a class="btn btn-primary mb-3 text-white" href="{{ url('admin/users/trashed') }}">View
                                        Trashed</a>
                                @endif --}}

                                <table class="table responsive" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Date & Time</th>
                                            <th>Name</th>
                                            <th>Package Enabled</th>
                                            <!-- <th>Subscription Expiry Date</th>
                                            <th>Plan Renewal</th> -->
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>CNIC Front</th>
                                            <th>CNIC Back</th>
                                            <th>Shop Images</th>
                                            <th>Profile Image</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                       
                                    </tbody>
                                </table>
                            </div> <!-- /.card-body -->
                        </div> <!-- /.card -->
                    </div> <!-- /.col -->
                </div> <!-- /.row -->
            </div> <!-- /.section-body -->
        </section>
    </div>


    <!-- Deactivation Reason Modal -->
    <div class="modal fade" id="deactivationModal" tabindex="-1" role="dialog" aria-labelledby="deactivationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deactivationModalLabel">Deactivation Reason</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="deactivationForm">
                        @csrf
                        <input type="hidden" name="user_id" id="deactivatingUserId">
                        <div class="form-group">
                            <label for="deactivationReason">Please specify the reason for deactivation:</label>
                            <textarea class="form-control" id="deactivationReason" name="reason" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmDeactivation">
                        Submit
                        <span id="deactivationLoader" class="spinner-border spinner-border-sm text-light ml-2"
                            role="status" style="display:none;"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cnicModal" tabindex="-1" aria-labelledby="cnicModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cnicModalLabel">CNIC Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="cnicFront" src="" class="img-fluid mb-3" alt="CNIC Front">
                    <img id="cnicBack" src="" class="img-fluid" alt="CNIC Back">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="shopImagesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Shop Images</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="shopCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner" id="shopImagesContainer"></div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#shopCarousel"
                            data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#shopCarousel"
                            data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection

@section('js')
<script>
    $(document).ready(function() {
        $(document).keydown(function (e) {
            if (e.key === "Escape") {
                $('.modal.show').modal('hide');
            }
        });

        // ===== Initialize DataTable with Responsiveness =====
        const table = $('#table_id_events').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('vendors.data') }}",

            responsive: true,
            autoWidth: false,
            pageLength: 10,
            destroy: true,

            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search vendors..."
            },

            columns: [
                { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false },
                { data: 'created_at' },
                { data: 'name' },
                { data: 'package' },
                // { data: 'subscription_expiry', orderable: false, searchable: false },
                // { data: 'plan_renewal', orderable: false, searchable: false },
                { data: 'email' },
                { data: 'phone' },
                { data: 'cnic_front', orderable: false, searchable: false },
                { data: 'cnic_back', orderable: false, searchable: false },
                { data: 'shop_images', orderable: false, searchable: false },
                { data: 'image', orderable: false, searchable: false },
                { data: 'status', orderable: false, searchable: false },
                { data: 'actions', orderable: false, searchable: false }
            ]
        });

        // ===== SweetAlert2 Delete Confirmation =====
        $(document).on('click', '.show_confirm', function(e) {
            e.preventDefault();
            const formId = $(this).data("form");
            const form = document.getElementById(formId);

            swal({
                title: "Are you sure you want to delete this record?",
                text: "If you delete this, it will be gone forever.",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then(function(willDelete) {
                if (willDelete) form.submit();
            });
        });

        // ===== Toggle Vendor Status =====
        let currentUserId = null;

        $(document).on('click', '.change-vendor-status', function() {
            const userId = $(this).data('user-id');
            const newStatus = $(this).data('new-status');

            if (newStatus === 'deactivated') {
                $('#deactivatingUserId').val(userId);
                $('#deactivationModal').modal('show');
            } else {
                updateVendorStatus(userId, newStatus);
            }
        });

        $('#confirmDeactivation').on('click', function() {
            const reason = $('#deactivationReason').val().trim();
            if (reason === '') {
                toastr.error('Please provide a deactivation reason');
                return;
            }

            $('#deactivationLoader').show();
            $('#confirmDeactivation').prop('disabled', true);

            const userId = $('#deactivatingUserId').val();
            updateVendorStatus(userId, 'deactivated', reason);
        });

        function updateVendorStatus(userId, status, reason = null) {
            $.ajax({
                url: "{{ route('vendor.update-status') }}",
                type: "POST",
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: { id: userId, status: status, reason: reason },
                 success: function(res) {
                        if (!res.success) {
                            toastr.error(res.message);
                            return;
                        }

                        toastr.success(res.message);

                        const colorClasses = {
                            pending: 'btn-warning',
                            activated: 'btn-primary',
                            deactivated: 'btn-danger',
                        };

                        // 🔥 Find the button that triggered this action
                        const btn = $(`.change-vendor-status[data-user-id="${userId}"]`)
                            .closest('.dropdown')
                            .find('.dropdown-toggle');

                        // ✅ Update button text & color
                        btn
                            .text(status.charAt(0).toUpperCase() + status.slice(1))
                            .removeClass('btn-warning btn-primary btn-danger')
                            .addClass(colorClasses[status]);
                                const statusOptions = {
                                pending: ['activated','deactivated'],
                                activated: ['deactivated'],
                                deactivated: ['activated']
                            };

                        // ✅ Rebuild dropdown menu
                        const dropdownMenu = btn.next('.dropdown-menu');
                        dropdownMenu.empty();

                        statusOptions[status].forEach(s => {
                            dropdownMenu.append(`
                                <button type="button"
                                    class="dropdown-item change-vendor-status"
                                    data-user-id="${userId}"
                                    data-new-status="${s}">
                                    ${s.charAt(0).toUpperCase() + s.slice(1)}
                                </button>
                            `);
                        });

                        $('#deactivationModal').modal('hide');
                        $('#deactivationReason').val('');
                    },
                error: function(xhr) {
                    console.error('Error:', xhr.status, xhr.responseText);
                    toastr.error('Error updating status');
                },
                complete: function() {
                    $('#deactivationLoader').hide();
                    $('#confirmDeactivation').prop('disabled', false);
                }
            });
        }


        // ===== CNIC Front Modal =====
        $(document).on('click', '.view-cnic', function() {
            const front = $(this).data('front');
            $('#cnicBack').hide();
            $('#cnicFront').attr('src', front).show();
            $('#cnicModal').modal('show');
        });

        // ===== CNIC Back Modal =====
        $(document).on('click', '.view-cnic-back', function() {
            const back = $(this).data('back');
            $('#cnicFront').hide();
            $('#cnicBack').attr('src', back).show();
            $('#cnicModal').modal('show');
        });

        // ===== Plan Renewal =====
        $(document).on('change', '.renew-plan-select', function() {
            const select = $(this);
            const vendorId = select.data('vendor-id');
            const planId = select.val();
            const planName = select.find('option:selected').data('plan-name');

            if (!planId) {
                return;
            }

            select.prop('disabled', true);

            swal({
                title: "Are you sure you want to renew this plan?",
                text: planName + " will be activated for 30 days starting today. Any remaining days on the current plan will not be carried forward.",
                icon: "warning",
                buttons: true,
                dangerMode: false,
            }).then(function(confirmed) {
                if (!confirmed) {
                    select.val('');
                    select.prop('disabled', false);
                    return;
                }

                $.ajax({
                    url: "{{ route('vendor.renew-plan') }}",
                    type: "POST",
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: {
                        vendor_id: vendorId,
                        subscription_plan_id: planId,
                    },
                    success: function(res) {
                        if (!res.success) {
                            toastr.error(res.message || 'Failed to renew plan');
                            select.val('');
                            return;
                        }

                        toastr.success(res.message);
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Error renewing plan';
                        toastr.error(message);
                        select.val('');
                    },
                    complete: function() {
                        select.prop('disabled', false);
                    }
                });
            });
        });

        // ===== Shop Images Modal =====
        $(document).on('click', '.view-shop-images', function() {
            const images = $(this).data('images');
            const container = $('#shopImagesContainer');
            container.empty();

            images.forEach((img, i) => {
                container.append(`
                    <div class="carousel-item ${i === 0 ? 'active' : ''}">
                        <img src="{{ asset('') }}${img}" class="d-block w-100" style="max-height:500px; object-fit:contain;">
                    </div>
                `);
            });

            $('#shopImagesModal').modal('show');
        });
    });
</script>

@endsection
