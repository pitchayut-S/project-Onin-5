<?php
// รวมสคริปต์ SweetAlert2 และฟังก์ชันแจ้งเตือนที่ใช้ร่วมกัน
if (!defined('ALERT_SCRIPTS_LOADED')) {
    define('ALERT_SCRIPTS_LOADED', true);
    ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // ปุ่มซ้าย = ยกเลิก, ปุ่มขวา = ตกลง
        function confirmLogout() {            
            Swal.fire({
                title: "ออกจากระบบ?",
                text: "คุณแน่ใจใช่ไหมที่จะออกจากระบบ",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "ตกลง",
                cancelButtonText: "ยกเลิก",
                reverseButtons: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "logout.php";
                }
            });
        }


    </script>
    <?php
}
?>
