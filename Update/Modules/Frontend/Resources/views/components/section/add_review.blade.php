<!-- Review Modal -->
<div class="modal fade rating-modal" id="rattingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <button class="custom-close-btn btn btn-primary" data-bs-dismiss="modal">
                    <i class="ph ph-x"></i>
                </button>
                <h5 class="mb-2">{{ __('frontend.share_movie_experience') }}</h5>
                <p class="m-0">{{ __('frontend.share_your_thoughts') }}</p>

                <div class="mt-5 pt-3">
                    <form class="m-0" id="reviewForm">
                        <ul class="list-inline m-0 p-0 d-flex align-items-center justify-content-center gap-3 rating-list">
                            @for ($i = 1; $i <= 5; $i++)
                                <li data-value="{{ $i }}" class="star">
                                    <span class="text-warning fs-4 icon">
                                        <i class="ph-fill ph-star icon-fill"></i>
                                        <i class="ph ph-star icon-normal"></i>
                                    </span>
                                </li>
                            @endfor
                        </ul>
                        <div id="ratingError" class="text-danger mt-2" style="display: none;"></div>

                        <div class="mt-5">
                            <textarea class="form-control" placeholder="{{ __('messages.share_thoughts_placeholder') }}" rows="4" id="reviewTextarea"></textarea>
                            <div id="reviewError" class="text-danger mt-2" style="display: none;"></div>
                        </div>

                        <div class="mt-5 pt-2">
                            <button type="submit" class="btn btn-primary" id="submitBtn">{{ __('frontend.submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedRating = 0;
let entertainmentId = null;
let reviewId = null;
let firstEdit = true;
let ratingStar = 0;
let ratingText = '';
const translations = {
    my_review: "{{ __('messages.my_review') }}",
    edit: "{{ __('messages.edit') }}",
    delete: "{{ __('messages.delete') }}"
};

$(document).ready(function () {
    const ratingModal = $('#rattingModal');

    $('#rattingModal').on('hidden.bs.modal', function () {
        $('.modal-backdrop').remove();
        $('body').css({ 'overflow': '', 'padding-right': '' });
        firstEdit = true;
    });

    ratingModal.on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);

        entertainmentId = button.attr('data-entertainment-id') || button.data('entertainment-id');
        reviewId = button.attr('data-review-id') || button.data('review-id');

        $('#ratingError').hide().text('');
        $('#reviewError').hide().text('');

        if (reviewId) {
            const rawReview = button.attr('data-review') ?? button.data('review');
            // Treat null or "null" as empty string to avoid showing literal "null" in modal
            const review = (rawReview === null || rawReview === undefined || rawReview === 'null') ? '' : rawReview;
            const rating = button.attr('data-rating') || button.data('rating') || 0;

            selectedRating = parseInt(rating) || 0;
            $('#reviewTextarea').val(review);
            highlightStars(selectedRating);

            ratingStar = selectedRating;
            ratingText = review;
        } else {
            resetRating();
            $('#reviewTextarea').val('');
            ratingStar = 0;
            ratingText = '';
        }
    });

    $('.star').on('click', function () {
        selectedRating = $(this).data('value');
        highlightStars(selectedRating);
        $('#ratingError').hide().text('');
    });

    $('#reviewTextarea').on('input', function () {
        if ($(this).val().trim() !== '') {
            $('#reviewError').hide().text('');
        }
    });

    let isSubmitting = false;

    $('#reviewForm').on('submit', function (event) {
        event.preventDefault();

        $('#ratingError').hide().text('');
        $('#reviewError').hide().text('');

        const textarea = $('#reviewTextarea').val().trim();
        const submitBtn = $('#submitBtn');
        let hasError = false;

        if (selectedRating === 0) {
            $('#ratingError').text("{{ __('messages.please_select_rating') }}").show();
            hasError = true;
        }


        if (hasError || isSubmitting) return;

        isSubmitting = true;
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> {{ __('messages.submitting') }}');

        const url = '{{ route('save-rating') }}?is_ajax=1';
        const method = 'POST';

        $.ajax({
            url: url,
            method: method,
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: JSON.stringify({
                entertainment_id: entertainmentId,
                rating: selectedRating,
                review: textarea,
                id: reviewId,
            }),
            success: function (data) {
                window.successSnackbar(data.message);
                const reviewdata = data.data;

                $('#reviweList').removeClass('d-none');
                $('#review-list').removeClass('d-none');
                $('#addratingbtn1').addClass('d-none');
                if ($('#addratingbtn').length) {
                    $('#addratingbtn').addClass('d-none');
                }

                $("#rattingModal").modal('hide');
                $('.modal-backdrop').remove();

                if (reviewId) {
                    firstEdit = false;
                    ratingStar = selectedRating;
                    ratingText = textarea;

                    const reviewCard = $('#your_review');
                    if (reviewCard.length) {
                        const reviewText = reviewCard.find('.review-detail p.mb-0.mt-3');
                        if (reviewText.length) {
                            reviewText.text(textarea);
                        } else {
                            const reviewDetail = reviewCard.find('.review-detail');
                            if (reviewDetail.length) {
                                reviewDetail.append(`<p class="mb-0 mt-3 fw-medium">${textarea}</p>`);
                            }
                        }

                        const starList = reviewCard.find('.review-detail ul.list-inline');
                        if (starList.length) {
                            starList.empty();
                            for (let i = 0; i < selectedRating; i++) {
                                starList.append('<li class="text-warning"><i class="ph-fill ph-star"></i></li>');
                            }
                        } else {
                            const starContainer = reviewCard.find('.review-detail .d-flex.align-items-center.gap-1').last();
                            if (starContainer.length) {
                                starContainer.empty();
                                for (let i = 0; i < selectedRating; i++) {
                                    starContainer.append('<i class="ph-fill ph-star text-warning"></i>');
                                }
                            }
                        }


                        const editButton = reviewCard.find('button[data-bs-toggle="modal"][data-bs-target="#rattingModal"]');
                        if (editButton.length) {
                            editButton.removeData('review');
                            editButton.removeData('rating');

                            editButton.attr('data-review', textarea);
                            editButton.attr('data-rating', selectedRating);

                            editButton.data('review', textarea);
                            editButton.data('rating', selectedRating);
                        }
                    }

                    $('#reviewTextarea').val(textarea);
                    highlightStars(selectedRating);
                } else {
                    ratingStar = reviewdata.rating;
                    ratingText = reviewdata.review;
                    const newReview = $('#reviewlist');

                    const reviewCard = `
                        <div id="your_review">
                            <div class="review-card">
                                <div class="mb-3 d-flex align-items-center justify-content-between">
                                    <h5 class="m-0">${translations.my_review}</h5>
                                    <div class="d-flex align-items-center gap-3">
                                        <button class="btn btn-link p-0 fw-semibold d-flex align-items-center gap-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#rattingModal"
                                                data-review-id="${reviewdata.id}"
                                                data-entertainment-id="${reviewdata.entertainment_id}"
                                                data-review="${reviewdata.review ? reviewdata.review : ''}"
                                                data-rating="${reviewdata.rating}">
                                            <i class="ph ph-pencil-line"></i>
                                        </button>
                                        <button type="button" class="btn btn-link p-0 fw-semibold d-flex align-items-center gap-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteratingModal"
                                                data-id="${reviewdata.id}"
                                                onclick="setDeleteId(${reviewdata.id})">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="review-detail rounded">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                        <div class="d-flex align-items-center justify-content-center gap-3">
                                            <img src="${reviewdata.profile_image}" alt="user" class="img-fluid user-img rounded-circle">
                                            <div>
                                                <h6 class="line-count-1 font-size-18">${reviewdata.username}</h6>
                                                <p class="mb-0 font-size-14-0">${(reviewdata.created_at)}</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            ${Array.from({ length: reviewdata.rating }, (_, i) => `<i class="ph-fill ph-star text-warning"></i>`).join('')}
                                        </div>
                                    </div>
                                    <p class="mb-0 mt-3 fw-medium">${reviewdata.review ? reviewdata.review : ''}</p>
                                </div>
                            </div>
                        </div>`;
                    newReview.append(reviewCard);
                }
            },
            error: function () {
                window.errorSnackbar("{{ __('messages.failed_to_submit_review') }}");
            },
            complete: function () {
                isSubmitting = false;
                submitBtn.prop('disabled', false).html('{{ __('frontend.submit') }}');
            }
        });
    });
});

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'Invalid Date';
    const day = date.toLocaleString('en-US', { day: 'numeric' });
    const month = date.toLocaleString('en-US', { month: 'long' });
    const year = date.toLocaleString('en-US', { year: 'numeric' });
    return `${day} ${month} ${year}`;
}

function resetRating() {
    selectedRating = 0;
    $('.star').removeClass('selected');
}

function highlightStars(rating) {
    $('.star').each(function () {
        const starValue = $(this).data('value');
        $(this).toggleClass('selected', starValue <= rating);
    });
}
</script>

<style>
.star.selected .icon-fill {
    display: inline;
}
.star.selected .icon-normal {
    display: none;
}
.text-danger {
    font-size: 0.9rem;
}
</style>
