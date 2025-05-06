// resources/js/Hooks/useSwal.js
import Swal from 'sweetalert2';

export const useSwal = () => {
  const toast = (icon, title, timer = 2500) =>
    Swal.fire({ toast:true, position:'top', icon, title,
                showConfirmButton:false, timer });

  const confirm = (title, text, onOk) =>
    Swal.fire({
      icon: 'question', title, text,
      showCancelButton:true, confirmButtonText:'Sí', cancelButtonText:'Cancelar'
    }).then(res => { if (res.isConfirmed) onOk(); });

  return { toast, confirm, modal: Swal.fire };
};
