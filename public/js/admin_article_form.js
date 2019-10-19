Dropzone.autoDiscover = false;

$(document).ready(function () {
    var referenceList = new ReferenceList($('.js-reference-list'));
    initializeDropzone(referenceList);

    var $locationSelect = $('.js-article-form-location');
    var $specificLocationTarget = $('.js-specific-location-target');

    $locationSelect.on('change', function (e) {
        $.ajax({
            url: $locationSelect.data('specific-location-url'),
            data: {
                location: $locationSelect.val()
            },
            success: function (html) {
                if (!html) {
                    $specificLocationTarget.find('select').remove();
                    $specificLocationTarget.addClass('d-none');

                    return;
                }

                // Replace the current field and show
                $specificLocationTarget
                    .html(html)
                    .removeClass('d-none')
            }
        });
    });
});

class ReferenceList {
    constructor($element) {
        this.$element = $element;
        this.references = [];
        this.render();

        this.$element.on('click', '.js-reference-delete', (event) => {
            this.handleReferenceDelete(event);
        });

        this.$element.on('blur', '.js-edit-filename', (event) => {
            this.handleReferenceEditFileName(event);
        });

        $.ajax({
            url: this.$element.data('url')
        }).then(data => {
            this.references = data;
            this.render();
        });
    }

    handleReferenceEditFileName(event) {
        const $li = $(event.currentTarget).closest('.list-group-item');
        const id = $li.data('id');
        const reference = this.references.find(reference => reference.id === id);
        reference.originalFilename = $(event.currentTarget).val();

        $.ajax({
            url: '/admin/article/references/' + id,
            method: 'PUT',
            data: JSON.stringify(reference)
        });
    }

    handleReferenceDelete(event) {
        const $li = $(event.currentTarget).closest('.list-group-item');
        const id = $li.data('id');
        $li.addClass('disabled');

        $.ajax({
            url: '/admin/article/references/' + id,
            method: 'DELETE'
        }).then(() => {
            this.references = this.references.filter(reference => reference.id !== id);
            this.render();
        });
    }

    addReference(reference) {
        this.references.push(reference);
        this.render();
    }

    render() {
        const itemsHtml = this.references.map(reference => {
            return `
                        <li class="list-group-item d-flex justify-content-between align-items-center" data-id="${reference.id}">
                            <input type="text" value="${reference.originalFilename}" class="form-control js-edit-filename" style="width: auto">
                        <span>
                            <a href="/admin/article/references/${reference.id}/download"><span class="fa fa-download" style="vertical-align: middle"></span></a>
                            <button class="js-reference-delete btn btn-link"><span class="fa fa-trash"></span></button>
                        </span>
                        </li>
                    `
        });
        this.$element.html(itemsHtml.join(''));
    }
}

function initializeDropzone(referenceList) {
    var formElement = document.querySelector('.js-reference-dropzone');

    if (!formElement) {
        return;
    }

    var dropzone = new Dropzone(formElement, {
        paramName: 'reference',
        init: function () {
            this.on('error', function (file, data) {
                if (data.detail) {
                    this.emit('error', file, data.detail);
                }
            });

            this.on('success', function (file, data) {
                referenceList.addReference(data);
            });
        }
    });
}
