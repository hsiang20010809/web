function toggleSubscription() {
  fetch('toggle_subscription.php', {
      method: 'POST',
      credentials: 'same-origin'
  })
  .then(response => response.json())
  .then(data => {
      if (data.success) {
          alert(data.message);
          const btn = document.getElementById('subscribeBtn');
          btn.textContent = data.isSubscribed ? '取消訂閱' : '訂閱';
      } else {
          alert('操作失敗：' + data.message);
      }
  })
  .catch(error => {
      console.error('Error:', error);
      alert('發生錯誤，請查看控制台以獲取詳細信息');
  });
}
