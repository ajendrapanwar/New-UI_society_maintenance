<style>
#pageLoader{
  position: fixed;
  top:0;
  left:0;
  width:100%;
  height:100%;
  background: rgba(35, 35, 35, 0.65);
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  z-index:9999;
  visibility:hidden;
}

/* spinner */
.loader{
  width:50px;
  height:50px;
  border:5px solid #e5e7eb;
  border-top:5px solid #2563eb;
  border-radius:50%;
  animation: spin 1s linear infinite;
}

/* text under spinner */
.loader-text{
  margin-top:12px;
  font-size:16px;
  font-weight:600;
  color:white;
  font-family:Arial, sans-serif;
}

@keyframes spin{
  0%{transform:rotate(0deg);}
  100%{transform:rotate(360deg);}
}
</style>

<div id="pageLoader">
  <div class="loader"></div>
  <div class="loader-text">Processing...</div>
</div>

<script>

const loader = document.getElementById("pageLoader");

/* show loader on form submit */
document.querySelectorAll("form").forEach(form=>{
  form.addEventListener("submit", function(){
      loader.style.visibility = "visible";
  });
});

/* show loader when page unloads */
window.addEventListener("beforeunload", function(){
  loader.style.visibility = "visible";
});

</script>