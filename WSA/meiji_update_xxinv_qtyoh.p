define input parameter inpdomain as character no-undo.
define input parameter inpsite as character no-undo.
define input parameter inpitem as character no-undo.
define input parameter inplot as character no-undo.
define input parameter inppick like xxinv_qtyoh.

def var location as character no-undo.

define output parameter outOK as logical no-undo initial false.
define output parameter outMsg as character no-undo initial "".

location = ''.

find first code_mstr where code_domain = inpdomain and code_fldname = 'mji_pack_dock'
no-lock no-error.
if avail code_mstr then location = code_value.

find first xxinv_det where xxinv_domain = inpdomain and xxinv_site = inpsite and
xxinv_part = inpitem and xxinv_loc = location and xxinv_lot = inplot
exclusive-lock no-error.
if avail xxinv_det then do:
    xxinv_qtyoh = xxinv_qtyoh - inppick.
end.


catch eSysError as Progress.Lang.SysError:
    outMsg = eSysError:GetMessage(1).
    delete object eSysError.
end catch.
