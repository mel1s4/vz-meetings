document.addEventListener('click', function (e) {
  if (e.target.getAttribute('data-vzclipboard') !== null) {
    const id = e.target.getAttribute('data-vzclipboard');
    const el = document.getElementById(id);
    el.focus();
    el.select();

    navigator.clipboard.writeText(el.value).then(function() {
      console.log('Text copied to clipboard');
    }).catch(function(err) {
      console.error('Could not copy text: ', err);
    });      
  }
});