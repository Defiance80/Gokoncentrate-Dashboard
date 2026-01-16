let baseUrl = '';
const baseUrlMeta = document.querySelector('meta[name="base-url"]');
if (baseUrlMeta) {
  baseUrl = baseUrlMeta.getAttribute('content');
}

const exampleModal = document.getElementById('exampleModal');
const mediaContainer = document.getElementById('media-container');
const mediaLibraryContent = document.getElementById('mediaLibraryContent');

document.addEventListener('DOMContentLoaded', function () {
  let selectedMediaUrl = '';
  let currentImageContainer = '';
  let currentHiddenInput = '';
  let videoInputCounter = 0; // Initialize a counter for dynamic IDs

  // Expose variables globally for external access
  window.mediaSelection = {
    setCurrentImageContainer: function(container) {
      currentImageContainer = container;
    },
    setCurrentHiddenInput: function(input) {
      currentHiddenInput = input;
    },
    getCurrentImageContainer: function() {
      return currentImageContainer;
    },
    getCurrentHiddenInput: function() {
      return currentHiddenInput;
    }
  };
  // Helper to enable/disable the Upload tab Save button based on upload status
  function updateUploadSubmitState() {
    const submitBtn = document.getElementById('submitButton');
    if (!submitBtn) return;
    const list = window.uploadedFiles || [];
    const active = list.filter(function (f) { return !f.removed; });
    const allDone = active.length > 0 && active.every(function (f) { return f.done === true; });
    submitBtn.disabled = !allDone;
  }
  // Expose globally for handlers defined outside this scope
  window.updateUploadSubmitState = updateUploadSubmitState;

  function initializeImageSelection(button) {
    button.addEventListener('click', function () {
      currentImageContainer = this.getAttribute('data-image-container');
      currentHiddenInput = this.getAttribute('data-hidden-input');
    });
  }

  // Expose globally for external use
  window.initializeImageSelection = initializeImageSelection;

  function initializeModal() {
    document.querySelectorAll('button[data-bs-target="#exampleModal"]').forEach(function (button) {
      initializeImageSelection(button);
    });
  }

  // Expose globally for external use
  window.initializeModal = initializeModal;

  function selectMedia(mediaUrl, mediaElement) {
    selectedMediaUrl = mediaUrl;

    // Remove active class from all media elements
    document.querySelectorAll('#mediaLibraryContent img, #mediaLibraryContent video').forEach(function (media) {
      media.classList.remove('iq-image');
    });

    // Add active class to the selected media element
    mediaElement.classList.add('iq-image');
  }

  const mediaLibraryContentElement = document.getElementById('mediaLibraryContent');
  if (mediaLibraryContentElement) {
    mediaLibraryContentElement.addEventListener('click', function (event) {

      if (event.target.tagName === 'IMG') {
        var mediaUrl = event.target.src;
        selectMedia(mediaUrl, event.target);
      } else if (event.target.tagName === 'VIDEO') {

        var mediaUrl = event.target.querySelector('source').src;
        //   var mediaUrl = event.target.src;
        if (mediaUrl) {

          selectMedia(mediaUrl, event.target);
        }
        event.preventDefault();
      }
    });
  }

  const mediaSubmitButton = document.getElementById('mediaSubmitButton');
  if (mediaSubmitButton) {
    mediaSubmitButton.addEventListener('click', function () {
      if (selectedMediaUrl && currentImageContainer && currentHiddenInput) {
        var selectedImageContainer = document.getElementById(currentImageContainer);
        var mediaUrlInput = document.getElementById(currentHiddenInput);

        if (selectedImageContainer) {
          mediaUrlInput.value = selectedMediaUrl;

          selectedImageContainer.innerHTML = '';

          // Check if there's an element with id iq-video-quality

          if (mediaUrlInput.hasAttribute('data-validation')) {
            var fileError = document.getElementById('file-error');
            var trailerFileError = document.getElementById('trailer-file-error');
            var videofile = document.querySelector('input[name="video_file_input"]');
            var vfi = document.querySelector('input[name="image_input4"]');
            var isTrailerField = mediaUrlInput.id === 'file_url_trailer' || mediaUrlInput.name === 'trailer_video';

            // Only allow video selection
            if (selectedMediaUrl.endsWith('.mp4') || selectedMediaUrl.endsWith('.avi') ||
                selectedMediaUrl.endsWith('.mov') || selectedMediaUrl.endsWith('.webm')) {
              if (fileError) {
                fileError.style.display = 'none';
              }
              if (trailerFileError) {
                trailerFileError.style.display = 'none';
              }
              if (videofile) {
                videofile.removeAttribute('required');
              }
              var video = document.createElement('video');
              video.src = selectedMediaUrl;
              video.controls = true;
              video.classList.add('img-fluid', 'mb-2');
              video.style.maxWidth = '300px';
              video.style.maxHeight = '300px';

              selectedImageContainer.appendChild(video);

              var crossIcon = document.createElement('span');
              crossIcon.innerHTML = '&times;';
              crossIcon.classList.add('remove-media-icon');
              crossIcon.style.cursor = 'pointer';
              crossIcon.style.fontSize = '24px';
              crossIcon.style.position = 'absolute';

              crossIcon.addEventListener('click', function () {
                selectedImageContainer.innerHTML = '';
                mediaUrlInput.value = '';
                if (videofile) {
                  videofile.value = '';
                }
                if (vfi) {
                  vfi.setAttribute('required', 'required');
                }

                if (fileError) {
                  fileError.style.display = 'block';
                }
                if (trailerFileError) {
                  trailerFileError.style.display = 'block';
                }
                // Clear any existing error messages
                var existingErrors = selectedImageContainer.querySelectorAll('.text-danger');
                existingErrors.forEach(function(err) {
                  err.remove();
                });
              });
              if (vfi) {
                vfi.removeAttribute('required');
              }
              selectedImageContainer.appendChild(crossIcon);
            } else {
              // Clear any existing error messages first
              var existingErrors = selectedImageContainer.querySelectorAll('.text-danger');
              existingErrors.forEach(function(err) {
                err.remove();
              });
              
              // Also clear errors from wrapper div if it's trailer field
              if (isTrailerField && mediaUrlInput.id === 'file_url3') {
                var wrapperDiv = document.querySelector('#url_file_input > div:last-child');
                if (wrapperDiv) {
                  var wrapperErrors = wrapperDiv.querySelectorAll('.text-danger');
                  wrapperErrors.forEach(function(err) {
                    if (err.textContent.includes('Only video files')) {
                      err.remove();
                    }
                  });
                }
              }

              // Clear the hidden input value since invalid file type was selected
              mediaUrlInput.value = '';

              if (videofile) {
                videofile.setAttribute('required', 'required');
              }
              if (fileError) {
                fileError.style.display = 'none';
              }
              if (trailerFileError) {
                trailerFileError.style.display = 'none';
              }

              // Only show error if a file was actually selected (not empty)
              if (selectedMediaUrl && selectedMediaUrl.trim() !== '') {
                // Show error for incorrect media type - make it more visible
                var errorElement = document.createElement('div');
                errorElement.classList.add('text-danger', 'mt-1');
                errorElement.style.fontSize = '14px';
                errorElement.style.fontWeight = '500';
                errorElement.style.display = 'block';
                errorElement.textContent = 'Only video files are allowed.';
                
                // For trailer video field, append error below input group instead of preview container
                if (isTrailerField && mediaUrlInput.id === 'file_url3') {
                  // Find the input group and append error after it
                  var inputGroup = document.querySelector('#url_file_input .input-group');
                  var wrapperDiv = document.querySelector('#url_file_input > div:last-child');
                  if (inputGroup && wrapperDiv) {
                    // Remove any existing error messages in wrapper
                    var existingErrors = wrapperDiv.querySelectorAll('.text-danger');
                    existingErrors.forEach(function(err) {
                      if (err.textContent.includes('Only video files')) {
                        err.remove();
                      }
                    });
                    // Append error after input group within wrapper
                    wrapperDiv.insertBefore(errorElement, inputGroup.nextSibling);
                  } else {
                    // Fallback to original location
                    selectedImageContainer.appendChild(errorElement);
                  }
                } else {
                  // For other fields, use original behavior
                  selectedImageContainer.appendChild(errorElement);
                }
              }
            }
          } else {

            if (selectedMediaUrl.endsWith('.png') || selectedMediaUrl.endsWith('.jpg') || selectedMediaUrl.endsWith('.jpeg') || selectedMediaUrl.endsWith('.webp')) {
              // For other cases, default behavior (assuming image upload or other media)
              var img = document.createElement('img');
              img.src = selectedMediaUrl;
              img.classList.add('img-fluid', 'mb-2');
              img.style.maxWidth = '100px';
              img.style.maxHeight = '100px';

              selectedImageContainer.appendChild(img);

              var crossIcon = document.createElement('span');
              crossIcon.innerHTML = '&times;';
              crossIcon.classList.add('remove-media-icon');
              crossIcon.style.cursor = 'pointer';
              crossIcon.style.fontSize = '24px';
              crossIcon.addEventListener('click', function () {
                selectedImageContainer.innerHTML = '';
                mediaUrlInput.value = '';
              });

              selectedImageContainer.appendChild(crossIcon);
            } else {
              var errorElement = document.createElement('div');
              errorElement.classList.add('text-danger');
              errorElement.textContent = 'Only image files are allowed.';
              selectedImageContainer.appendChild(errorElement);

              var buttonElements = document.querySelectorAll('.input-group-text.form-control');
              buttonElements.forEach(function (buttonElement) {
                if (buttonElement) {
                  buttonElement.innerHTML = '';
                }
              });
            }
          }

          $('#exampleModal').modal('hide');
        }
      }
    });
  }

  const submitButton = document.getElementById('submitButton');
  if (submitButton) {
    // default disabled until uploads completed
    submitButton.disabled = true;
    submitButton.addEventListener('click', function (event) {
      const mediaContainerdata = document.getElementById('media-container');

      event.preventDefault(); // Prevent the default form submission

      // Check if chunk uploads are still in progress
      window.uploadedFiles = window.uploadedFiles || [];
      var activeFiles = window.uploadedFiles.filter(function (f) { return !f.removed; });
      var allDone = activeFiles.length > 0 && activeFiles.every(function (f) { return f.done === true; });
      
      // If files are still uploading, don't proceed
      if (activeFiles.length > 0 && !allDone) {
        alert('Please wait for all files to finish uploading before saving.');
        return false;
      }

      // Disable button immediately when clicked
      submitButton.disabled = true;
      submitButton.innerText = 'Loading...';
      window.uploadedFiles = window.uploadedFiles || [];
      var formData = new FormData();
      var remainingFiles = window.uploadedFiles.filter(file => !file.removed);

      if (remainingFiles.length > 0) {
        document.getElementById('file_url_media').removeAttribute('required');
        document.getElementById('file_url_media-error').style.display = 'none';
        for (var i = 0; i < remainingFiles.length; i++) {
          formData.append('file_url[]', remainingFiles[i].file);
        }

        // Add type value to FormData
        var typeInput = document.getElementById('page_type');
        if (typeInput) {
          formData.append('page_type', typeInput.value);
        }

        // Submit the form with remaining files
        var xhr = new XMLHttpRequest();
        xhr.open('POST', `${baseUrl}/app/media-library/store`, true);
        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content')); // Set CSRF token header
        xhr.onloadstart = function () {
          submitButton.innerText = 'Loading...';
          submitButton.disabled = true;
        };

        xhr.onload = function () {
          if (xhr.status === 200) {
            window.uploadedFiles = [];
            // Trigger the media library tab to refresh
            var libTab = document.getElementById('nav-media-library-tab');
            if (libTab && typeof libTab.click === 'function') {
              libTab.click();
            }

            submitButton.disabled = false;
            submitButton.innerText = 'Save';

            // Check if FileManager is available and if we're in a folder
            if (typeof FileManager !== 'undefined' && FileManager && FileManager.state && FileManager.state.currentFolder) {
              // We're in a folder, reload the folder contents
              const currentFolder = FileManager.state.currentFolder;
              const mediaLibraryContent = document.getElementById('mediaLibraryContent');
              if (mediaLibraryContent) {
                mediaLibraryContent.innerHTML = ''; // Clear the container
              }
              // Reset pagination state
              FileManager.state.nextOffset = 0;
              FileManager.state.infiniteInitDone = false;
              // Reload folder contents with a delay to ensure file is processed
              // Files may be processed asynchronously, so we wait a bit longer
              setTimeout(function() {
                if (typeof FileManager.loadFolderContents === 'function') {
                  FileManager.loadFolderContents(currentFolder);
                }
              }, 1000); // Increased delay to 1 second for async file processing
            } else {
              // We're at root level, use the old pagination method
              const mediaContainer = document.getElementById('media-container');
              page = 1; // Reset the page to 1
              if (mediaContainer) {
                mediaContainer.innerHTML = ''; // Clear the container to load fresh content
              }

              loadPaginatedImages(); // Call the pagination function

              // Add the scroll event listener after the initial load
              if (mediaLibraryContent) {
                mediaLibraryContent.addEventListener('scroll', handleScroll);
              }
            }

            var uploadedImagesCont = document.getElementById('uploadedImages');
            if (uploadedImagesCont) {
              uploadedImagesCont.innerHTML = '';
            }
          }
        };

        xhr.onerror = function () {
          submitButton.disabled = false;
          submitButton.innerText = 'Save';
          alert('Upload failed. Please try again.');
        };

        xhr.onloadend = function () {
          // Only update text if upload failed (success case handled in onload)
          if (xhr.status !== 200) {
            submitButton.innerText = 'Save';
            submitButton.disabled = false;
          }
        };

        xhr.send(formData);
      } else {
        document.getElementById('file_url_media').setAttribute('required', 'required');
        document.getElementById('file_url_media-error').style.display = 'block';

        submitButton.innerText = 'Save';
        submitButton.disabled = false;
      }

      if (window.location.href === `${baseUrl}/app/media-library`) {
        window.location.reload();
      }
    });
  }

  function loadPaginatedImages() {

    const mediaContainer = document.getElementById('media-container');
    const loadingSpinner = document.getElementById('loading-spinner');
    const mediaLibraryContent = document.getElementById('mediaLibraryContent');
    const perPage = 21; // Number of images per page (adjust as needed)

    let isLoading = false;
    let hasMore = true;
    if (isLoading || !hasMore) return; // Prevent duplicate loads

    isLoading = true; // Set loading to true
    fetch(`${baseUrl}/app/media-library/getMediaStore?page=${page}&perPage=${perPage}`)
      .then(response => response.json())
      .then(data => {
        if (data.html) {
          if (page === 1) {
            mediaContainer.innerHTML = ''; // Clear existing content only on the first page load
          }

          mediaContainer.insertAdjacentHTML('beforeend', data.html);

          if (data.hasMore) {
            page++; // Increment page number if more images are available
          } else {
            hasMore = false; // Set hasMore to false if no more images
          }
        } else {
          console.log("No data received");
        }
      })
      .catch(error => console.error('Error:', error))
      .finally(() => {
        isLoading = false; // Reset loading status after the fetch completes
      });
  }

  // Scroll event handler to trigger loading more images
  function handleScroll() {

    // Check if the user has scrolled to the bottom of the mediaLibraryContent container
    if (mediaLibraryContent && mediaLibraryContent.scrollTop + mediaLibraryContent.clientHeight >= mediaLibraryContent.scrollHeight - 100) {
      loadPaginatedImages(); // Load more images when the user scrolls to the bottom
    }
  }

  if (exampleModal) {
    exampleModal.addEventListener('hidden.bs.modal', function () {
      if (mediaLibraryContent) {
        mediaLibraryContent.removeEventListener('scroll', handleScroll);
      }
    });
  }

  function handleVideoQualityTypeChange(section) {
    section.find('.video_quality_type').on('change', function () {
      var selectedType = $(this).val();
      var QualityVideoFileInput = section.find('.quality_video_file_input');
      var QualityVideoURLInput = section.find('.quality_video_input');
      var qualityVideoInput = section.find('input[name="quality_video[]"]');
      var qualityVideoURLInput = section.find('input[name="quality_video_url_input[]"]');
      if (selectedType === 'Local') {
        QualityVideoFileInput.removeClass('d-none');
        QualityVideoURLInput.addClass('d-none');
        qualityVideoInput.val(qualityVideoInput.val()).trigger('change');
        qualityVideoURLInput.val('').trigger('change');
      } else {
        QualityVideoFileInput.addClass('d-none');
        QualityVideoURLInput.removeClass('d-none');
        qualityVideoURLInput.val(qualityVideoURLInput.val()).trigger('change');
        qualityVideoInput.val('').trigger('change');
      }
    }).trigger('change');
  }

  // Download quality type toggle (URL vs Local) for edit/create download quality rows
  function handleDownloadQualityTypeChange(section) {
    section.find('.download_quality_video_type').on('change', function () {
      var selectedType = $(this).val();
      var fileInputSec = section.find('.download-video-file-input');
      var urlInputSec = section.find('.download-video-url-input');

      // Hide both, then show needed one
      fileInputSec.addClass('d-none');
      urlInputSec.addClass('d-none');

      if (selectedType === 'Local') {
        fileInputSec.removeClass('d-none');
      } else if (selectedType === 'URL') {
        urlInputSec.removeClass('d-none');
      }
    }).trigger('change');
  }
  function destroySelect2(section) {
    section.find('select.select2').each(function () {
      if ($(this).data('select2')) {
        $(this).select2('destroy');
      }
    });
  }

  function initializeSelect2(section) {
    section.find('select.select2').each(function () {
      $(this).select2({
        width: '100%'
      });
    });
  }
  function initializeFormState() {
    // Handle the initial visibility of input fields based on current values
    $('.video-inputs-container').each(function () {
      handleVideoQualityTypeChange($(this));
    });
    // Initialize clips sections if present
    $('.clips-inputs-container').each(function () {
      handleClipUploadTypeChange($(this));
    });
    // Initialize existing download quality rows
    $('.download-video-inputs-container').each(function () {
      handleDownloadQualityTypeChange($(this));
    });
  }
  $('#add_more_video').click(function () {
    var originalSection = $('.video-inputs-container').first();
    destroySelect2(originalSection);

    var newSection = originalSection.clone();
    videoInputCounter++; // Increment the counter

    newSection.find('input, select').each(function () {
      var idAttr = $(this).attr('id');
      if (idAttr) {
        $(this).attr('id', idAttr + videoInputCounter);
      }

      var nameAttr = $(this).attr('name');
      if (nameAttr) {
        $(this).attr('name', nameAttr + videoInputCounter);
      }

      $(this).val('').trigger('change');
    });

    newSection.find('.remove-video-input').removeClass('d-none');

    newSection.find('[data-image-container]').each(function () {
      var dataAttr = $(this).attr('data-image-container');
      $(this).attr('data-image-container', dataAttr + videoInputCounter);
    });

    newSection.find('[data-hidden-input]').each(function () {
      var dataAttr = $(this).attr('data-hidden-input');
      $(this).attr('data-hidden-input', dataAttr + videoInputCounter);
    });

    newSection.find('.img-fluid').remove();
    newSection.find('.remove-media-icon').remove();
    newSection.find('input[type="hidden"]').val('');

    newSection.find('div[id]').each(function () {
      var idAttr = $(this).attr('id');
      if (idAttr) {
        $(this).attr('id', idAttr + videoInputCounter);
      }
    });

    $('#video-inputs-container-parent').append(newSection);

    initializeSelect2(newSection);
    handleVideoQualityTypeChange(newSection);
    initializeModal();
    initializeSelect2(originalSection);
  });

  $(document).on('click', '.remove-video-input', function () {
    $(this).closest('.video-inputs-container').remove();
  });

  // ===================== DOWNLOAD VIDEO SECTION =====================
  $('#add_more_download_video').click(function () {
    var originalSection = $('.download-video-inputs-container').first();
    destroySelect2(originalSection);

    var newSection = originalSection.clone();
    videoInputCounter++; // Increment the counter

    newSection.find('input, select').each(function () {
      var idAttr = $(this).attr('id');
      if (idAttr) {
        $(this).attr('id', idAttr + videoInputCounter);
      }

      var nameAttr = $(this).attr('name');
      if (nameAttr) {
        $(this).attr('name', nameAttr + videoInputCounter);
      }

      $(this).val('').trigger('change');
    });

    newSection.find('.remove-download-video-input').removeClass('d-none');

    newSection.find('[data-image-container]').each(function () {
      var dataAttr = $(this).attr('data-image-container');
      $(this).attr('data-image-container', dataAttr + videoInputCounter);
    });

    newSection.find('[data-hidden-input]').each(function () {
      var dataAttr = $(this).attr('data-hidden-input');
      $(this).attr('data-hidden-input', dataAttr + videoInputCounter);
    });

    newSection.find('.img-fluid').remove();
    newSection.find('.remove-media-icon').remove();
    newSection.find('input[type="hidden"]').val('');

    newSection.find('div[id]').each(function () {
      var idAttr = $(this).attr('id');
      if (idAttr) {
        $(this).attr('id', idAttr + videoInputCounter);
      }
    });

    $('#download-video-inputs-container-parent').append(newSection);

    initializeSelect2(newSection);
    handleDownloadQualityTypeChange(newSection);
    initializeModal();
    initializeSelect2(originalSection);
  });

  $(document).on('click', '.remove-download-video-input', function () {
    $(this).closest('.download-video-inputs-container').remove();
  });

  // ===================== CLIPS SECTION =====================
  let clipInputCounter = 0;

  function handleClipUploadTypeChange(section) {
    var clipTypeSelect = section.find('.clip_upload_type');

    function setVisibility(selectedType) {
      var clipVideoFileInput = section.find('.clip_video_file_input');
      var clipVideoURLInput = section.find('.clip_video_input');
      var clipVideoEmbedInput = section.find('.clip_video_embed_input');

      clipVideoFileInput.addClass('d-none');
      clipVideoURLInput.addClass('d-none');
      clipVideoEmbedInput.addClass('d-none');

      if (selectedType === 'Local') {
        clipVideoFileInput.removeClass('d-none');
      } else if (selectedType === 'Embedded' || selectedType === 'Embed') {
        clipVideoEmbedInput.removeClass('d-none');
      } else if (selectedType === 'URL' || selectedType === 'YouTube' || selectedType === 'HLS' || selectedType === 'Vimeo' || selectedType === 'x265') {
        clipVideoURLInput.removeClass('d-none');
      }
    }

    // Initial visibility without clearing any existing values
    setVisibility(clipTypeSelect.val());

    // On user change, update visibility and clear irrelevant fields
    clipTypeSelect.off('change.clip').on('change.clip', function (e) {
      var selectedType = $(this).val();
      setVisibility(selectedType);

      if (e.originalEvent) {
        var clipFileHidden = section.find('input[name="clip_file_input[]"]');
        var clipUrlInput = section.find('input[name="clip_url_input[]"]');
        var clipEmbedTextarea = section.find('textarea[name="clip_embedded[]"]');

        if (selectedType === 'Local') {
          if (clipUrlInput.length) clipUrlInput.val('').trigger('change');
          if (clipEmbedTextarea.length) clipEmbedTextarea.val('').trigger('change');
        } else if (selectedType === 'Embedded' || selectedType === 'Embed') {
          if (clipUrlInput.length) clipUrlInput.val('').trigger('change');
          if (clipFileHidden.length) clipFileHidden.val('').trigger('change');
        } else {
          if (clipFileHidden.length) clipFileHidden.val('').trigger('change');
          if (clipEmbedTextarea.length) clipEmbedTextarea.val('').trigger('change');
        }
      }
    });
  }

  // Function to update visibility of first clip's delete button
  function updateFirstClipDeleteButton() {
    var clipBlocks = $('.clip-block');
    var firstClipBlock = clipBlocks.first();
    var firstClipDeleteButton = firstClipBlock.find('.remove-clip-input');
    
    // Show delete button on first clip if there are more than 2 clips
    if (clipBlocks.length > 2) {
      firstClipDeleteButton.removeClass('d-none');
    } else {
      firstClipDeleteButton.addClass('d-none');
    }
  }

  // Remove any existing handlers to prevent duplicate attachments
  $('#add_more_clip').off('click').on('click', function () {
    var originalBlock = $('.clip-block').first();
    if (originalBlock.length === 0) return;

    destroySelect2(originalBlock);
    var newBlock = originalBlock.clone();
    clipInputCounter++;

    newBlock.find('input, select, textarea').each(function () {
      var idAttr = $(this).attr('id');
      if (idAttr) {
        $(this).attr('id', idAttr + '_clip' + clipInputCounter);
      }
      var nameAttr = $(this).attr('name');
      if (nameAttr) {
        $(this).val('');
      }
    });

    newBlock.find('.remove-clip-input').removeClass('d-none');

    newBlock.find('[data-image-container]').each(function () {
      var dataAttr = $(this).attr('data-image-container');
      $(this).attr('data-image-container', dataAttr + '_clip' + clipInputCounter);
    });

    newBlock.find('[data-hidden-input]').each(function () {
      var dataAttr = $(this).attr('data-hidden-input');
      $(this).attr('data-hidden-input', dataAttr + '_clip' + clipInputCounter);
    });

    newBlock.find('.img-fluid').remove();
    newBlock.find('.remove-media-icon').remove();
    newBlock.find('input[type="hidden"]').val('');

    newBlock.find('div[id]').each(function () {
      var idAttr = $(this).attr('id');
      if (idAttr) {
        $(this).attr('id', idAttr + '_clip' + clipInputCounter);
      }
    });

    $('#add_more_clip').closest('.text-end').before(newBlock);

    initializeSelect2(newBlock);
    handleClipUploadTypeChange(newBlock.find('.clips-inputs-container'));
    initializeModal();
    initializeSelect2(originalBlock);
    
    // Update first clip delete button visibility
    updateFirstClipDeleteButton();
  });

  $(document).on('click', '.remove-clip-input', function () {
    $(this).closest('.clip-block').remove();
    // Update first clip delete button visibility after removal
    updateFirstClipDeleteButton();
  });
  
  // Initialize on page load
  updateFirstClipDeleteButton();
  initializeFormState();
  initializeModal();
  initializeSelect2($(document));
});




if (document.getElementById('file_url_media')) {
  document.getElementById('file_url_media').addEventListener('change', function () {
    var fileInput = document.getElementById('file_url_media');
    var uploadedImagesContainer = document.getElementById('uploadedImages');
    var chunkSize = 1024 * 1024 * 30; // 100 MB chunk size (adjust as necessary)
    var uploadedFiles = [];

    // Clear previously uploaded images and reset progress
    uploadedImagesContainer.innerHTML = '';

    if (fileInput.files.length > 0) {
      for (var i = 0; i < fileInput.files.length; i++) {
        var file = fileInput.files[i];
        var start = 0;
        var end = Math.min(chunkSize, file.size);
        var index = 0;

        if (file.type.startsWith('video/')) {
          var video = document.createElement('video');
          video.src = URL.createObjectURL(file);
          video.currentTime = 1; // Capture frame at 1 second

          video.addEventListener('loadeddata', function () {
            var canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            var ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            var img = document.createElement('img');
            img.src = canvas.toDataURL('image/jpeg');
            img.classList.add('img-fluid', 'iq-uploaded-image');
            img.style.width = '150px'; // Adjust size as needed
            img.style.height = '100px';

            // Create progress bar
            var progressBar = document.createElement('div');
            progressBar.classList.add('progress', 'mb-3', 'iq-progress');
            progressBar.style.visibility = 'hidden'; // Change visibility to hidden initially
            progressBar.innerHTML = `
                            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        `;

            // Create close icon
            var closeButton = document.createElement('div');
            closeButton.classList.add('iq-uploaded-image-close');
            closeButton.innerHTML = '&times;';
            closeButton.addEventListener('click', function () {
              uploadedFiles[index].removed = true; // Mark file as removed
              this.parentNode.remove(); // Remove image on close icon click
              checkAndClearFileInput();
            });

            // Append image, progress bar, and close icon
            var imageContainer = document.createElement('div');
            imageContainer.classList.add('iq-uploaded-image-container');
            imageContainer.appendChild(img);
            imageContainer.appendChild(progressBar);
            imageContainer.appendChild(closeButton);
            uploadedImagesContainer.appendChild(imageContainer);

            // Track the uploaded file
            var itemIndex = uploadedFiles.length;
            uploadedFiles.push({ file: file, removed: false, progressBar: progressBar.querySelector('.progress-bar'), done: false });
            updateUploadSubmitState();

            // Start upload and make progress bar visible immediately
            progressBar.style.visibility = 'visible';
            uploadChunk(file, itemIndex, start, end, chunkSize, uploadedFiles, progressBar); // Pass progressBar to uploadChunk
          });
        } else {
          var reader = new FileReader();
          reader.onload = (function (file, index) {
            return function (e) {
              var img = document.createElement('img');
              img.src = e.target.result;
              img.classList.add('img-fluid', 'iq-uploaded-image');
              img.style.width = '150px'; // Adjust size as needed
              img.style.height = '100px';

              // Create progress bar
              var progressBar = document.createElement('div');
              progressBar.classList.add('progress', 'my-3', 'iq-progress');
              progressBar.style.visibility = 'hidden'; // Change visibility to hidden initially
              progressBar.innerHTML = `
                                <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            `;

              // Create close icon
              var closeButton = document.createElement('div');
              closeButton.classList.add('iq-uploaded-image-close');
              closeButton.innerHTML = '&times;';
              closeButton.addEventListener('click', function () {
                uploadedFiles[index].removed = true; // Mark file as removed
                this.parentNode.remove(); // Remove image on close icon click
                checkAndClearFileInput();
              });

              // Append image, progress bar, and close icon
              var imageContainer = document.createElement('div');
              imageContainer.classList.add('iq-uploaded-image-container');
              imageContainer.appendChild(img);
              imageContainer.appendChild(progressBar);
              imageContainer.appendChild(closeButton);
              uploadedImagesContainer.appendChild(imageContainer);

              // Track the uploaded file
              var itemIndex = uploadedFiles.length;
              uploadedFiles.push({ file: file, removed: false, progressBar: progressBar.querySelector('.progress-bar'), done: false });
              updateUploadSubmitState();

              // Start upload and make progress bar visible immediately
              progressBar.style.visibility = 'visible';
              uploadChunk(file, itemIndex, start, end, chunkSize, uploadedFiles, progressBar); // Pass progressBar to uploadChunk
            };
          })(file, i);

          reader.readAsDataURL(file);
        }
      }
    }

    // Track the uploaded files globally
    window.uploadedFiles = uploadedFiles;
    function checkAndClearFileInput() {
      // If all files are removed
      if (uploadedFiles.every(file => file.removed)) {
        fileInput.value = null; // Clear the file input
        uploadedFiles.length = 0; // Clear the uploadedFiles array
        document.getElementById('file_url_media').setAttribute('required', 'required');
        document.getElementById('file_url_media-error').style.display = 'block';
      }
      updateUploadSubmitState();
    }
  });
}

function uploadChunk(file, index, start, end, chunkSize, uploadedFiles, progressBar) { // Added progressBar parameter
  var chunk = file.slice(start, end);
  var formData = new FormData();
  formData.append('file_chunk', chunk);
  formData.append('index', index);
  formData.append('total_chunks', Math.ceil(file.size / chunkSize));
  formData.append('file_name', file.name);

  // AJAX request to upload chunk
  var xhr = new XMLHttpRequest();

  // Track upload progress
  xhr.upload.addEventListener('progress', function (e) {
    if (e.lengthComputable) {
      // cumulative percent across whole file, not just current chunk
      var uploadedSoFar = start + e.loaded;
      var percentComplete = Math.min(100, (uploadedSoFar / file.size) * 100);
      uploadedFiles[index].progressBar.style.width = percentComplete + '%';
      progressBar.style.visibility = 'visible'; // Ensure visible for all chunks
    }
  });

  xhr.open('POST', `${baseUrl}/app/media-library/upload`, true);
  xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content')); // Set CSRF token header

  xhr.onload = function () {
    if (xhr.status === 200) {
      var response = JSON.parse(xhr.responseText);
      if (response.success) {
        if (end < file.size) {
          start = end;
          end = Math.min(start + chunkSize, file.size);
          uploadChunk(file, index, start, end, chunkSize, uploadedFiles, progressBar); // Pass progressBar to uploadChunk
        } else {
          uploadedFiles[index].progressBar.style.width = '100%';
          progressBar.style.visibility = 'hidden'; // Hide progress bar after completion
          // mark file as done and update submit state
          var found = uploadedFiles.find(function (f) { return f.file === file; });
          if (found) { found.done = true; }
          updateUploadSubmitState();
        }
      }
    }
  };

  xhr.send(formData);
}

/////////////////////////////////////////  set image /////////////////////////////////////////

document.addEventListener('DOMContentLoaded', function () {
  // const mediaContainer = document.getElementById('media-container');
  const loadingSpinner = document.getElementById('loading-spinner');
  const mediaLibraryContent = document.getElementById('mediaLibraryContent');
  const searchInput = document.getElementById('media-search');
  const noData = document.getElementById('no_data');
  let page = 1;
  let isLoading = false;
  let hasMore = true;
  let searchQuery = ''; // Variable to store the search query
  let issearch = 0; // Variable to hold the debounce timeout ID
  const baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content'); // Adjust if necessary
  let noAvailableMessageShown = false; // Flag to prevent multiple "No Available" messages

  function loadImages(query = '') {


    if (isLoading || (!hasMore && query == '' && issearch == 0)) return;

    isLoading = true;
    loadingSpinner.style.display = 'block';

    fetch(`${baseUrl}/app/media-library/getMediaStore?page=${page}&query=${encodeURIComponent(query)}`)
      .then(response => {

        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {

        // clearNoAvailableMessage();

        if (data.html) {

          noData.classList.add('d-none');
          if (issearch == 1) {
            mediaContainer.innerHTML = '';
          }

          mediaContainer.insertAdjacentHTML('beforeend', data.html);
          page++;
          noAvailableMessageShown = false; // Reset flag if new images are loaded

          issearch.value = 0;


        } else {

          issearch.value = 0;
          mediaContainer.innerHTML = '';
          noData.classList.remove('d-none');

          $('#no_data').text('No data available');
        }

        hasMore = data.hasMore;
      })
      .catch(error => {
        console.error('Error loading images:', error);
      })
      .finally(() => {
        isLoading = false;
        loadingSpinner.style.display = 'none';
      });
  }

  function onScroll() {

    if (mediaLibraryContent && mediaLibraryContent.scrollTop + mediaLibraryContent.clientHeight >= mediaLibraryContent.scrollHeight - 100) {
      loadImages(searchQuery);
    }
  }

  function handleSearchInput() {
    searchQuery = searchInput.value;

    page = 1;
    mediaContainer.innerHTML = '';

    loadImages(searchQuery);

  }
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      issearch.value = 1;
      handleSearchInput()
    });

  }

  const clearSearchButton = document.getElementById('clear-search');

  function toggleClearButtonVisibility() {
    if (searchInput) {
      if (searchInput.value.length > 0) {
        clearSearchButton.classList.remove('d-none'); // Show the button
      } else {
        clearSearchButton.classList.add('d-none'); // Hide the button
      }
    }
  }
  if (searchInput) {
    // Add event listener for input changes
    searchInput.addEventListener('input', toggleClearButtonVisibility);
  }
  // Add event listener for clear button
  if (clearSearchButton) {
    clearSearchButton.addEventListener('click', function () {
      searchInput.value = ''; // Clear the input field
      toggleClearButtonVisibility(); // Update button visibility
      page = 1; // Reset page number
      searchQuery = ''; // Reset search query
      isLoading = false;
      hasMore = true;
      issearch.value = 0;
      mediaContainer.innerHTML = '';
      loadImages(searchQuery);
    });
  }
  // Initialize the visibility on page load
  toggleClearButtonVisibility();


  if (exampleModal) {

    exampleModal.addEventListener('shown.bs.modal', function () {
      if (mediaContainer && mediaContainer.children.length === 0) {
        loadImages(searchQuery); // Load images based on the search query if present
      }
      if (mediaLibraryContent) {
        mediaLibraryContent.addEventListener('scroll', onScroll);
      }
    });

    exampleModal.addEventListener('hidden.bs.modal', function () {
      if (mediaLibraryContent) {
        mediaLibraryContent.removeEventListener('scroll', onScroll);
      }
    });

    if (mediaContainer && mediaContainer.children.length === 0) {
      loadImages(searchQuery);
    }

  }

});


document.addEventListener('DOMContentLoaded', function () {
  const uploadButton = document.getElementById('nav-upload-files-tab');
  const libraryButton = document.getElementById('nav-media-library-tab');
  const searchContainer = document.getElementById('media-search-container');

  // Only proceed if at least one of the tab buttons exists on the page
  if (libraryButton || uploadButton) {

    // Function to toggle the search container visibility
    function toggleSearchVisibility() {
      const isLibraryActive = libraryButton && libraryButton.classList.contains('active');

      if (isLibraryActive) {
        if (searchContainer) {
          searchContainer.style.display = 'block';
        }
      } else {
        if (searchContainer) {
          searchContainer.style.display = 'none';
        }
      }
    }

    // Initial toggle based on the active tab (run only if libraryButton exists)
    if (libraryButton) {
      toggleSearchVisibility();
    }

    // Add event listeners to toggle the visibility on tab change (guarded)
    if (uploadButton) uploadButton.addEventListener('click', toggleSearchVisibility);
    if (libraryButton) libraryButton.addEventListener('click', toggleSearchVisibility);
  }
});


function showValidationModal(errors, fieldLabels = {}) {
  const existingModal = document.getElementById('validationModal')
  if (existingModal) existingModal.remove()

  // Use a Set to track unique error messages and avoid duplicates
  // Use message content as key to catch duplicates even if field names/labels differ
  const seenMessages = new Set()
  const errorList = Object.entries(errors)
    .map(([field, messages]) => {
      // Only process the first error message for each field
      if (!messages || messages.length === 0) {
        return ''
      }
      
      const pretty = field.replace(/_/g, ' ').replace(/\b\w/g, function (l) { return l.toUpperCase() })
      let label = fieldLabels[field] || pretty
      
      // Check field name first (more reliable than label) for grouping
      const fieldLower = field.toLowerCase()
      if (fieldLower.includes('subtitle')) {
        label = fieldLabels['subtitles'] || label
      } else if (fieldLower.includes('clip')) {
        label = fieldLabels['clips'] || label
      } else if (fieldLower.includes('download')) {
        label = fieldLabels['download_info'] || label
      } else if (label.toLowerCase().includes('subtitle')) {
        label = fieldLabels['subtitles'] || label
      } else if (label.toLowerCase().includes('clip')) {
        label = fieldLabels['clips'] || label
      } else if (label.toLowerCase().includes('download')) {
        label = fieldLabels['download_info'] || label
      }

      // Get the first error message only
      const message = messages[0] ? messages[0].trim() : ''
      
      if (!message) {
        return ''
      }
      
      // Use message content as unique key to prevent duplicates
      // This catches cases where same message comes from different field names
      const messageKey = message
      
      // Skip if we've already seen this exact error message
      if (seenMessages.has(messageKey)) {
        return ''
      }
      
      seenMessages.add(messageKey)
      return `<li><strong>${label}:</strong> ${message}</li>`
    })
    .filter(item => item !== '') // Remove empty strings from duplicates
    .join('')

  // Get translations from window object or use fallbacks
  const translations = window.validationTranslations || {
    validation_errors: 'Validation Errors',
    please_correct_errors: 'Please correct the following errors:',
    close: 'Close'
  };

  const modalHtml = `
    <div class="modal fade" id="validationModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">${translations.validation_errors}</h5>
            <button type="button" class="btn-close btn-close-white bg-white text-primary" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>${translations.please_correct_errors}</p>
            <ul>${errorList}</ul>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${translations.close}</button>
          </div>
        </div>
      </div>
    </div>
  `

  document.body.insertAdjacentHTML('beforeend', modalHtml)
  const modal = new bootstrap.Modal(document.getElementById('validationModal'))
  modal.show()
}

// Global error count on tabs (dynamic tab/field mapping)
function showErrorCountOnTabs(errors, tabFields = {}) {
  document.querySelectorAll('.error-badge').forEach(function (badge) { badge.remove() })

  Object.keys(tabFields).forEach(function (tabId) {
    const fields = tabFields[tabId] || []
    let errorCount = 0

    // Check for exact field matches
    errorCount += fields.filter(function (field) {
      return field !== '*' && !!errors[field]
    }).length

    // Check for wildcard patterns (like subtitles.*)
    const wildcardFields = fields.filter(function (field) {
      return field.includes('*')
    })

    wildcardFields.forEach(function (wildcardField) {
      const pattern = wildcardField.replace('*', '.*')
      const regex = new RegExp('^' + pattern + '$')

      Object.keys(errors).forEach(function (errorField) {
        if (regex.test(errorField)) {
          errorCount++
        }
      })
    })

    if (errorCount > 0) {
      const tabButton = document.querySelector('[data-bs-target="#' + tabId + '"]')
      if (tabButton) {
        const badge = document.createElement('span')
        badge.className = 'badge bg-danger error-badge ms-2'
        badge.textContent = errorCount
        tabButton.appendChild(badge)
      }
    }
  })
}

window.showValidationModal = showValidationModal
window.showErrorCountOnTabs = showErrorCountOnTabs




