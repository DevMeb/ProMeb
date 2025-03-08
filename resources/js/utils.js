import { useToast } from 'vue-toastification';

const toast = useToast();

export function notify(type, message) {
  if (type === 'success') {
    toast.success(message);
  } else if (type === 'error') {
    toast.error(message);
  } else if (type === 'info') {
    toast.info(message);
  } else if (type === 'warning') {
    toast.warning(message);
  } 
}
import dayjs from "dayjs";

export function formatDate(date) {
  return dayjs(date).format('DD/MM/YYYY'); // format dd/mm/yyyy
}