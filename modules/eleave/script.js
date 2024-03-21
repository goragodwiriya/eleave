function initEleaveLeave() {
  var num_days = 0,
    doLeaveTypeChanged = function() {
      send(WEB_URL + 'index.php/eleave/model/leave/datas', 'id=' + $E('leave_id').value, function(xhr) {
        var maxDate, ds = xhr.responseText.toJSON();
        if (ds) {
          $G('leave_detail').innerHTML = ds.detail.unentityify();
          num_days = ds.num_days;
          var start_date = $G('start_date').value;
          if (num_days == 0) {
            maxDate = null;
          } else if (start_date != '') {
            maxDate = new Date(start_date).moveDate(num_days - 1);
          }
          $G('end_date').max = maxDate;
          $G('end_date').min = start_date;
        } else if (xhr.responseText != '') {
          console.log(xhr.responseText);
        }
      });
    };
  $G('leave_id').addEvent('change', doLeaveTypeChanged);
  doLeaveTypeChanged.call(this);
  $G('start_date').addEvent("change", function() {
    if (this.value) {
      $G('end_date').min = this.value;
      if (num_days > 0) {
        var maxDate = new Date(this.value).moveDate(num_days - 1);
        $G('end_date').max = maxDate;
      }
    }
  });
}
