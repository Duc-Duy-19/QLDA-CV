document.addEventListener("DOMContentLoaded", function () {
    const applicationsList = document.getElementById("applications-list");
    const totalApplications = document.getElementById("total-applications");
    const pendingApplications = document.getElementById("pending-applications");
    const interviewApplications = document.getElementById(
        "interview-applications"
    );
    const acceptedApplications = document.getElementById(
        "accepted-applications"
    );

    // Modal & Confirm Dialog
    const modal = document.getElementById("applicationModal");
    const modalBody = document.getElementById("modalBody");
    const confirmationDialog = document.getElementById("confirmationDialog");
    const confirmationMessage = document.getElementById("confirmationMessage");
    const confirmCancelBtn = document.getElementById("confirmCancelBtn");

    let applications = [];
    let currentCancelId = null;

    // Load applications
    function loadApplications() {
        applicationsList.innerHTML = `
            <div class="empty-state">
                <i>⏳</i>
                <h3>Đang tải dữ liệu...</h3>
                <p>Vui lòng chờ trong giây lát</p>
            </div>
        `;

        fetch("/api/applications")
            .then((res) => res.json())
            .then((data) => {
                applications = data;
                renderApplications();
                updateStats();
            })
            .catch(() => {
                applicationsList.innerHTML = `
                    <div class="empty-state">
                        <i>⚠️</i>
                        <h3>Lỗi khi tải dữ liệu</h3>
                        <p>Không thể kết nối đến server</p>
                    </div>
                `;
            });
    }

    // Render danh sách
    function renderApplications(filtered = null) {
        const list = filtered || applications;

        if (list.length === 0) {
            applicationsList.innerHTML = `
                <div class="empty-state">
                    <i>📭</i>
                    <h3>Chưa có ứng tuyển nào</h3>
                    <p>Bắt đầu nộp CV để theo dõi trạng thái ứng tuyển</p>
                </div>
            `;
            return;
        }

        applicationsList.innerHTML = list
            .map(
                (app) => `
            <div class="application-card">
                <h3>${app.job_title}</h3>
                <p>Công ty: ${app.company_name}</p>
                <p>Ngày nộp: ${new Date(app.applied_date).toLocaleDateString(
                    "vi-VN"
                )}</p>
                <p>Trạng thái: <span class="status ${
                    app.status
                }">${translateStatus(app.status)}</span></p>
                <div class="actions">
                    <button onclick="viewDetails(${
                        app.id
                    })" class="btn btn-info">
                        <i class="fas fa-eye"></i> Xem chi tiết
                    </button>
                    ${
                        app.status !== "cancelled"
                            ? `
                        <button onclick="cancelApplication(${app.id})" class="btn btn-danger">
                            <i class="fas fa-times"></i> Hủy ứng tuyển
                        </button>
                    `
                            : ""
                    }
                </div>
            </div>
        `
            )
            .join("");
    }

    // Update thống kê
    function updateStats() {
        totalApplications.textContent = applications.length;
        pendingApplications.textContent = applications.filter(
            (a) => a.status === "pending"
        ).length;
        interviewApplications.textContent = applications.filter(
            (a) => a.status === "reviewed"
        ).length;
        acceptedApplications.textContent = applications.filter(
            (a) => a.status === "approved"
        ).length;
    }

    // Bộ lọc
    window.applyFilters = function () {
        const status = document.getElementById("status-filter").value;
        const from = document.getElementById("date-from").value;
        const to = document.getElementById("date-to").value;

        let filtered = applications;

        if (status) filtered = filtered.filter((a) => a.status === status);

        if (from)
            filtered = filtered.filter(
                (a) => new Date(a.applied_date) >= new Date(from)
            );

        if (to)
            filtered = filtered.filter(
                (a) => new Date(a.applied_date) <= new Date(to)
            );

        renderApplications(filtered);
    };

    // View chi tiết
    window.viewDetails = function (id) {
        const app = applications.find((a) => a.id === id);
        if (!app) return;

        modalBody.innerHTML = `
            <p><strong>Công việc:</strong> ${app.job_title}</p>
            <p><strong>Công ty:</strong> ${app.company_name}</p>
            <p><strong>Ngày nộp:</strong> ${new Date(
                app.applied_date
            ).toLocaleDateString("vi-VN")}</p>
            <p><strong>Trạng thái:</strong> ${translateStatus(app.status)}</p>
            <p><strong>Ghi chú:</strong> ${app.notes || "Không có"}</p>
        `;

        modal.style.display = "block";
    };

    window.closeModal = function () {
        modal.style.display = "none";
    };

    // Cancel Application
    window.cancelApplication = function (id) {
        currentCancelId = id;
        confirmationMessage.textContent =
            "Bạn có chắc chắn muốn hủy ứng tuyển này? Hành động này không thể hoàn tác.";
        confirmationDialog.style.display = "flex";
    };

    window.closeConfirmation = function () {
        confirmationDialog.style.display = "none";
        currentCancelId = null;
    };

    window.confirmCancelApplication = function () {
        if (!currentCancelId) return;

        fetch(`/api/applications/${currentCancelId}/cancel`, { method: "POST" })
            .then((res) => res.json())
            .then(() => {
                applications = applications.map((a) =>
                    a.id === currentCancelId ? { ...a, status: "cancelled" } : a
                );
                renderApplications();
                updateStats();
                closeConfirmation();
            })
            .catch(() => {
                alert("Có lỗi xảy ra khi hủy ứng tuyển!");
            });
    };

    // Utils
    function translateStatus(status) {
        switch (status) {
            case "pending":
                return "Chờ duyệt";
            case "reviewed":
                return "Đã xem";
            case "approved":
                return "Phù hợp";
            case "rejected":
                return "Không phù hợp";
            case "cancelled":
                return "Đã hủy";
            default:
                return status;
        }
    }

    // Đóng modal khi click ngoài
    window.onclick = function (event) {
        if (event.target === modal) {
            closeModal();
        }
        if (event.target === confirmationDialog) {
            closeConfirmation();
        }
    };

    // Load khi vào trang
    loadApplications();
});
