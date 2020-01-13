(function () {
    // перенести в UserSettings модуль
    let fileuploadInited = false;
    $(".UserSettingsAvatar").on("click", function () {
        if (!fileuploadInited) {
            let script = document.createElement("script");
            script.src = "lightpublic/js/fileupload.min.js";
            script.type = "text/javascript";
            script.async = true;
            script.onload = function () {
                $("#UserSettingsAvatarInputfile").fileupload({
                    url: "index.php?a=Core&ps=UploadUserAvatar",
                    dataType: "json",
                    sequentialUploads: true,
                    disableImageResize: /Android(?!.*Chrome)|Opera/.test(window.navigator && navigator.userAgent),
                    imageMaxWidth: 800,
                    imageMaxHeight: 800,
                    imageCrop: true,
                    done: function (_, data) {
                        if (data.result.error) {
                            Light.toastError(data.result.text);
                        } else {
                            const newSrc = `index.php?a=Core&ps=GetUserAvatar&time=${Date.now()}`;
                            $(".MainmenuAvatar")
                                .find("img.avatar")
                                .attr("src", newSrc);
                            $(".UserSettingsAvatar")
                                .find("img")
                                .attr("src", newSrc);
                        }
                    }
                });
                fileuploadInited = true;
                document.getElementById('UserSettingsAvatarInputfile').click();
            };
            document.getElementsByTagName("head")[0].appendChild(script);
        } else {
            document.getElementById('UserSettingsAvatarInputfile').click();
        }
    });
})();
