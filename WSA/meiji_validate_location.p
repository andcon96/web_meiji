define input parameter inpdomain as character no-undo.
define input parameter inpitem as character no-undo.
define input parameter inpwhs as character no-undo.
define input parameter inplevel as character no-undo.
define input parameter inpbin as character no-undo.

define output parameter outOK as logical no-undo initial false.
define output parameter outMsg as character no-undo initial "".

find first xxinv_det where xxinv_domain = inpdomain and xxinv_part = inpitem and
xxinv_wrh = inpwhs and xxinv_level = inplevel and xxinv_bin = inpbin no-lock no-error.
if avail xxinv_det then outOK = true.

catch eSysError as Progress.Lang.SysError:
    outMsg = eSysError:GetMessage(1).
    delete object eSysError.
end catch.
