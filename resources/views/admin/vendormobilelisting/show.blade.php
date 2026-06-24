@extends('admin.layout.app')
@section('title', 'Product Images & Videos')

@section('content')
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <a class="btn btn-primary mb-3" href="{{ route('vendormobile.index') }}">Back</a>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Product Images & Videos</h4>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">

                                <table class="table" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Images/Videos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($mobiles as $mobile)
                                            @if ($mobile && is_object($mobile))
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                  <td>
                                                        @php
                                                                $images = json_decode($mobile->image, true) ?? [];
                                                                $videos = json_decode($mobile->video, true) ?? [];

                                                                $media = [];

                                                                foreach ($images as $img) {
                                                                    $media[] = ['type' => 'image', 'src' => asset($img)];
                                                                }

                                                                foreach ($videos as $vid) {
                                                                    $media[] = ['type' => 'video', 'src' => asset($vid)];
                                                                }
                                                            @endphp

                                                            @if(!empty($media))
                                                                @php $first = $media[0]; @endphp

                                                                {{-- Thumbnail --}}
                                                                @if($first['type'] === 'image')
                                                                    <img src="{{ $first['src'] }}"
                                                                        style="width:50px; height:50px; cursor:pointer;"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#imageModal"
                                                                        data-media='@json($media)'
                                                                        data-start-index="0">
                                                                @else
                                                                    <video width="60" height="50" style="cursor:pointer;"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#imageModal"
                                                                        data-media='@json($media)'
                                                                        data-start-index="0">
                                                                        <source src="{{ $first['src'] }}">
                                                                    </video>
                                                                @endif
                                                            @else
                                                                <span class="text-muted">No Images/Videos</span>
                                                            @endif
                                                        </td>
                                                </tr>
                                            @endif
                                        @endforeach

                                    </tbody>
                                </table>
                            </div> <!-- /.card-body -->
                        </div> <!-- /.card -->
                    </div> <!-- /.col -->
                </div> <!-- /.row -->
            </div> <!-- /.section-body -->
        </section>
    </div>


<!-- Modal Structure -->
<div id="imageModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-transparent shadow-none border-0">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="imageCarousel" class="carousel slide" data-bs-interval="false">
                    <div class="carousel-inner" id="carouselImages">
                        <!-- Images injected by JS -->
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#imageCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#imageCarousel" data-bs-slide="next">
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
            // Initialize DataTable
            if ($.fn.DataTable.isDataTable('#table_id_events')) {
                $('#table_id_events').DataTable().destroy();
            }
            $('#table_id_events').DataTable();

            // SweetAlert2 delete confirmation
            $('.show_confirm').click(function(event) {
                event.preventDefault();
                var formId = $(this).data("form");
                var form = document.getElementById(formId);

                swal({
                   title: "Are you sure you want to delete this record?",
                    text: "If you delete this, it will be gone forever.",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        form.submit();
                    }
                });
            });
             

        });

        
   document.addEventListener("DOMContentLoaded", function () {
    var imageModal = document.getElementById('imageModal');
    var carouselInner = document.getElementById('carouselImages');

    imageModal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        var mediaList = JSON.parse(trigger.getAttribute('data-media'));
        var startIndex = parseInt(trigger.getAttribute('data-start-index'), 10);

        carouselInner.innerHTML = '';

        mediaList.forEach((item, index) => {
            var div = document.createElement('div');
            div.classList.add('carousel-item');
            if (index === startIndex) div.classList.add('active');

            if (item.type === "image") {
                div.innerHTML = `
                    <img src="${item.src}" class="img-fluid"
                         style="max-height:65vh; max-width:85%; object-fit:contain;">
                `;
            } else if (item.type === "video") {
                div.innerHTML = `
                    <video controls class="w-100" 
                           style="max-height:65vh; max-width:70%; object-fit:contain;">
                        <source src="${item.src}" type="video/mp4">
                    </video>
                `;
            }

            carouselInner.appendChild(div);
        });

        var carousel = new bootstrap.Carousel(document.getElementById('imageCarousel'), {
            interval: false,
            ride: false
        });

        carousel.to(startIndex);
    });
});




    </script>
@endsection
