import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

document.addEventListener('DOMContentLoaded', () => {
    showFlashMessage();

    document.addEventListener('submit', async (event) => {
        const form = event.target instanceof HTMLFormElement
            ? event.target
            : null;

        if (
            form === null
            || !form.matches('[data-swal-confirm]')
            || form.dataset.swalConfirmed === '1'
        ) {
            return;
        }

        event.preventDefault();

        const submitter = event.submitter instanceof HTMLElement
            ? event.submitter
            : null;

        const result = await Swal.fire({
            icon: form.dataset.swalIcon || 'warning',
            title: form.dataset.swalTitle || '¿Confirmar operación?',
            text: form.dataset.swalText || '',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            confirmButtonText: form.dataset.swalConfirmText || 'Confirmar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: form.dataset.swalDanger === '1'
                ? '#d63939'
                : '#206bc4',
            cancelButtonColor: '#6c7a91',
            allowOutsideClick: false,
            allowEscapeKey: true,
        });

        if (!result.isConfirmed) {
            return;
        }

        form.dataset.swalConfirmed = '1';

        if (submitter !== null) {
            form.requestSubmit(submitter);
            return;
        }

        form.requestSubmit();
    });
});

function showFlashMessage() {
    const element = document.getElementById(
        'guardian-page-feedback'
    );

    if (element === null) {
        return;
    }

    let feedback = {};

    try {
        feedback = JSON.parse(
            element.textContent || '{}'
        );
    } catch (error) {
        console.error(
            'No se pudo interpretar el mensaje de la página.',
            error
        );

        return;
    }

    if (feedback.success) {
        Swal.fire({
            icon: 'success',
            title: 'Operación realizada',
            text: feedback.success,
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#206bc4',
        });

        return;
    }

    if (feedback.error) {
        Swal.fire({
            icon: 'error',
            title: 'No se pudo completar',
            text: feedback.error,
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#206bc4',
        });
    }
}