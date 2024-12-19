document.querySelector(".open-button").addEventListener("click", function () {
    const door = document.querySelector(".door");
    door.classList.remove("closed");
    door.classList.add("open");
});
