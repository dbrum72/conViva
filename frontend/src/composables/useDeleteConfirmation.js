import { ref } from "vue";

export function useDeleteConfirmation() {
  const itemToDelete = ref(null);
  const deleting = ref(false);

  function requestDelete(item) {
    itemToDelete.value = item;
  }

  function cancelDelete() {
    if (deleting.value) {
      return;
    }

    itemToDelete.value = null;
  }

  function clearDelete() {
    itemToDelete.value = null;
  }

  return {
    itemToDelete,
    deleting,
    requestDelete,
    cancelDelete,
    clearDelete,
  };
}
