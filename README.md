**Description:**

 This is the XigmaNAS CBSD Extension for quickly create and manage bhyve VMs.



**Installation**

1) Install module, XigmaNAS:

*Tools > Command > Command* (paste line):
```
fetch --no-verify-peer https://raw.githubusercontent.com/cbsd/xigmabhyve/main/utils/cbsdbhyve_install.sh  && chmod +x cbsdbhyve_install.sh && ./cbsdbhyve_install.sh && echo "=> Done!"
```

2) Make sure the 'Disable Extension Menu' checkbox is unset, XigmaNAS UI:

System > Advanced Setup -> Disable Extension Menu [ ] Disable scanning of folders for existing extension menus.

3) Initialize the working directory of the cbsd to any existing pool:

Extensions > CBSD bhyve

![image](https://github.com/cbsd/xigmajail/assets/926409/7bc1c494-486e-48a6-aea3-4174caa47ec6)

**Credits:**

 Oleg Ginzburg (olevole)

Additional information on CBSD: <a href="https://github.com/cbsd/cbsd">https://github.com/cbsd/cbsd</a>
