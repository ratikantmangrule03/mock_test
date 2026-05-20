// ==========================================
// SHARED UI LOGIC ONLY
// ==========================================

function formatTime(seconds) {
  if (seconds == null || isNaN(seconds)) return "--:--";
  let m = Math.floor(seconds / 60)
    .toString()
    .padStart(2, "0");
  let s = (seconds % 60).toString().padStart(2, "0");
  return `${m}m ${s}s`;
}

function toggleDropdown(id) {
  document.getElementById(id).classList.toggle("active");
}

window.onclick = function (event) {
  if (!event.target.closest(".nav-profile")) {
    document
      .querySelectorAll(".dropdown")
      .forEach((d) => d.classList.remove("active"));
  }
};
