ZIPFILE = Plugin.zip
BUILD_TARGET = *.php config.json src/

.PHONY: all
all: clean
	zip --exclude $(EXCLUDE_TARGET) -r $(ZIPFILE) $(BUILD_TARGET)

.PHONY: clean
clean:
	rm -f $(ZIPFILE)
