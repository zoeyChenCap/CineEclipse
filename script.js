document.getElementById('signUp').addEventListener('click', function() {
    window.location.href = "register.php"; 
});

document.getElementById('logIn').addEventListener('click', function() {
    window.location.href = "login.php"; 
});

// Check the password equals confirm password or not and prevent it from submitting
document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form");
    const errorDiv = document.getElementById("error");

    //1. 页面加载时，恢复所有表单数据
    if (localStorage.getItem("formData")) {
        Object.entries(JSON.parse(localStorage.getItem("formData"))).forEach(([key, value]) => {
            if (document.getElementById(key)) {
                document.getElementById(key).value = value;
            }
        });
    }

    //2. 监听表单输入事件，存储所有数据
    form.addEventListener("input", function () {
        const formData = Object.fromEntries(new FormData(form).entries());
        localStorage.setItem("formData", JSON.stringify(formData));
    });

    //3. 监听提交事件，验证密码是否一致
    form.addEventListener("submit", function (event) {
        const formData = JSON.parse(localStorage.getItem("formData")) || {};
        if (formData.password !== formData.confirm_password) {
            event.preventDefault(); // 阻止提交
            errorDiv.innerHTML = "Passwords do not match. Please keep the password consistent.";
            errorDiv.style.color = "red";
        } else {
            localStorage.removeItem("formData"); // 成功提交后清除存储数据
        }
    });
});



