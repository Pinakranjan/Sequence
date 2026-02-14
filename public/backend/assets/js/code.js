$(function(){
    $(document).on('click','#delete',function(e){
        e.preventDefault();
        var link = $(this).attr("href");

        var showDialog = $(this).data('show-dialog');
        if (typeof showDialog === 'undefined') {
            showDialog = true;
        } else if (showDialog === 'false' || showDialog === '0' || showDialog === 0) {
            showDialog = false;
        } else {
            showDialog = Boolean(showDialog);
        }

        Swal.fire({
            title: 'Are you sure?',
            text: "Delete This Data?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = link
                if (showDialog) {
                    Swal.fire(
                        'Deleted!',
                        'Your file has been deleted.',
                        'success'
                    );
                }
            }
        })
    });
});
