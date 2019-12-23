var route ="";
var temp_row = null;
$(document).ready(function () {
    let table = $('#productos-table').DataTable({
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        iDisplayLength: 50,
        "language": {
            "sLengthMenu":     "Mostrar _MENU_ empresas",
            "sInfo":           "Mostrando empresas del _START_ al _END_ de  _TOTAL_ ",
            "sSearch":         "Buscar:",
            "oPaginate": {
                "sPrevious": "Anterior"
            },
        }
    });
    $('#productos-nuevo').on('click', function () {
        document.getElementById('productos-form').reset();
        $('#productos-modal').modal();
        route = "/producto/new";
    });
    table.on('click', '.edit' ,function(){
        $tr = $(this).closest('tr');
        temp_row = $tr;
        var data = table.row($tr).data();
        route = "/producto/"+data[0]+"/edit";
        $('#clave').val(data[1]);
        $('#nombre').val(data[2]);
        $('#precio').val(data[3]);
        $('#productos-modal').modal();
    });
    table.on('click', '.delete' ,function(){
        $tr = $(this).closest('tr');
        var data = table.row($tr).data();
        remove(data[0]);
        table.row($tr).remove().draw();
    });
    $('#productos-form').on('submit', function(e){
        e.preventDefault();
        add(table);
    });
});

function add(table){
    $.ajax({
        type: "post",
        url: route,
        data:  {
            'data':{
                'clave': $('#clave').val(),
                'nombre': $('#nombre').val(),
                'precio': $('#precio').val(),
            }
        },
        dataType: "json",
        success: function (response) {
            if(response.id == null){
                alert('El código del producto ya existe, intenta otro.');
            }else{
                if(route.includes("edit")){
                    table.row(temp_row).remove().draw();
                }
                addRow(response, table, false);
                $('#productos-modal').modal('hide');
            }
        }
    });
}
function remove(id){
    $.ajax({
        type: "delete",
        url: "/producto/"+id,
        success: function (response) {
            if(response.is_deleted){
                
            }
        }
    });
}
function addRow(data, table, updated){
    let row = table.row.add([
        data.id,
        data.claveProducto,
        data.nombre,
        data.precio,
        '<td><button type="button" class="btn btn-warning edit">Editar</button> <button type="button" class="btn btn-danger delete">Eliminar</button></td>'
    ]).draw().node();
}