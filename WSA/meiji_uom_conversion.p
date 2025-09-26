define input parameter inpdomain as character no-undo.
define input parameter inpnbr as character no-undo.
define input parameter inpline as integer no-undo.

define output parameter outOK as logical no-undo initial false.
define output parameter outMsg as character no-undo initial "".

define temp-table temp
field t_so_qty_conversion like sod_um_conv
.

define output parameter table for temp.

find first sod_det where sod_domain = inpdomain and sod_nbr = inpnbr and sod_line = inpline
no-lock no-error.
if avail sod_det then do:
    outOK = yes.
    create temp.
    assign t_so_qty_conversion = sod_um_conv.
end.

catch eSysError as Progress.Lang.SysError:
    outMsg = eSysError:GetMessage(1).
    delete object eSysError.
end catch.
