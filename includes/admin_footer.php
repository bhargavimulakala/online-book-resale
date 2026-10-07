<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer" style="z-index:9999;"></div>
<!-- Bootstrap Modal backdrop fix -->
<script>
// Show mobile sidebar on toggle
document.addEventListener('DOMContentLoaded',function(){
    const btn=document.getElementById('sidebarToggle');
    const sb=document.getElementById('adminSidebar');
    if(btn&&sb){
        btn.addEventListener('click',function(){
            sb.classList.toggle('collapsed');
            document.querySelector('.admin-content')?.classList.toggle('expanded');
        });
    }
});
</script>
</body>
</html>
