define input parameter inpdomain as character no-undo.
define input parameter inpcust as character no-undo.

define variable itemDescription as character format "x(50)" no-undo.

define output parameter outOK as logical no-undo initial false.
define output parameter outMsg as character no-undo initial "".

define temp-table temp
field t_so_domain       like so_domain
field t_so_site         like so_site
field t_so_ship         like so_ship
field t_so_nbr          like so_nbr
field t_so_line         like sod_line
field t_so_part         like sod_part
field t_so_part_desc    as char format "x(50)"
field t_so_ord_qty      like sod_qty_ord
.

define output parameter table for temp.

for each so_mstr where so_domain = inpdomain and so_cust = inpcust no-lock:
    for each sod_det where sod_domain = so_domain and sod_nbr = so_nbr no-lock:
        itemDescription = ''.
        find first pt_mstr where pt_domain = sod_domain and pt_part = sod_part no-lock no-error.
        if avail pt_mstr then do:
            if pt_desc2 <> '' then do:
                itemDescription = pt_desc1 + ' ' + pt_desc2.
            end.
            else do:
                itemDescription = pt_desc1.
            end.
        end.
        outOK = yes.
        create temp.
        assign
            temp.t_so_domain    = so_domain
            temp.t_so_site      = so_site
            temp.t_so_ship      = so_ship
            temp.t_so_nbr       = so_nbr
            temp.t_so_line      = sod_line
            temp.t_so_part      = sod_part
            temp.t_so_part_desc = itemDescription
            temp.t_so_ord_qty   = sod_qty_ord
        .
    end.
end.

catch eSysError as Progress.Lang.SysError:
    outMsg = eSysError:GetMessage(1).
    delete object eSysError.
end catch.
