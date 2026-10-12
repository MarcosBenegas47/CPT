document.addEventListener("DOMContentLoaded", function () {

    const inputs = document.querySelectorAll(".nota");

    function guardar(input) {

        if (input.value === "") return;

        const data = new FormData();
        data.append("auto_guardar", 1);
        data.append("nota", input.value);
        data.append("id_inscripcion", input.dataset.id);

        fetch("plantilla.php?modulo=notas", {
            method: "POST",
            body: data
        })
        .then(() => {
            input.style.background = "#d4edda";
            setTimeout(() => input.style.background = "", 300);
        });
    }

    inputs.forEach((input, index) => {

        input.addEventListener("blur", function () {
            guardar(this);
        });

        input.addEventListener("keydown", function(e) {

            if (e.key === "Enter") {
                e.preventDefault();
                guardar(this);
                if (inputs[index + 1]) inputs[index + 1].focus();
            }

            if (e.key === "ArrowDown") {
                e.preventDefault();
                if (inputs[index + 1]) inputs[index + 1].focus();
            }

            if (e.key === "ArrowUp") {
                e.preventDefault();
                if (inputs[index - 1]) inputs[index - 1].focus();
            }

        });

    });

});