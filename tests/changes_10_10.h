
FOUNDATION_EXPORT NSString *NSURLFileScheme;
FOUNDATION_EXPORT NSString * const NSURLKeysOfUnsetValuesKey NS_AVAILABLE(10_7, 5_0); // Key for the resource properties that have not been set after setResourceValues:error: returns an error, returned as an array of of strings.

FOUNDATION_EXPORT NSString * const NSURLNameKey                        NS_AVAILABLE(10_6, 4_0); // The resource name provided by the file system (Read-write, value type NSString)
FOUNDATION_EXPORT NSString * const NSURLLocalizedNameKey               NS_AVAILABLE(10_6, 4_0); // Localized or extension-hidden name as displayed to users (Read-only, value type NSString)
FOUNDATION_EXPORT NSString * const NSURLIsRegularFileKey               NS_AVAILABLE(10_6, 4_0); // True for regular files (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLIsDirectoryKey                 NS_AVAILABLE(10_6, 4_0); // True for directories (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLIsSymbolicLinkKey              NS_AVAILABLE(10_6, 4_0); // True for symlinks (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLIsVolumeKey                    NS_AVAILABLE(10_6, 4_0); // True for the root directory of a volume (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLIsPackageKey                   NS_AVAILABLE(10_6, 4_0); // True for packaged directories (Read-only 10_6 and 10_7, read-write 10_8, value type boolean NSNumber). Note: You can only set or clear this property on directories; if you try to set this property on non-directory objects, the property is ignored. If the directory is a package for some other reason (extension type, etc), setting this property to false will have no effect.
FOUNDATION_EXPORT NSString * const NSURLIsSystemImmutableKey           NS_AVAILABLE(10_6, 4_0); // True for system-immutable resources (Read-write, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLIsUserImmutableKey             NS_AVAILABLE(10_6, 4_0); // True for user-immutable resources (Read-write, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLIsHiddenKey                    NS_AVAILABLE(10_6, 4_0); // True for resources normally not displayed to users (Read-write, value type boolean NSNumber). Note: If the resource is a hidden because its name starts with a period, setting this property to false will not change the property.
FOUNDATION_EXPORT NSString * const NSURLHasHiddenExtensionKey          NS_AVAILABLE(10_6, 4_0); // True for resources whose filename extension is removed from the localized name property (Read-write, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLCreationDateKey                NS_AVAILABLE(10_6, 4_0); // The date the resource was created (Read-write, value type NSDate)
FOUNDATION_EXPORT NSString * const NSURLContentAccessDateKey           NS_AVAILABLE(10_6, 4_0); // The date the resource was last accessed (Read-only, value type NSDate)
FOUNDATION_EXPORT NSString * const NSURLContentModificationDateKey     NS_AVAILABLE(10_6, 4_0); // The time the resource content was last modified (Read-write, value type NSDate)
FOUNDATION_EXPORT NSString * const NSURLAttributeModificationDateKey   NS_AVAILABLE(10_6, 4_0); // The time the resource's attributes were last modified (Read-write, value type NSDate)
FOUNDATION_EXPORT NSString * const NSURLLinkCountKey                   NS_AVAILABLE(10_6, 4_0); // Number of hard links to the resource (Read-only, value type NSNumber)
FOUNDATION_EXPORT NSString * const NSURLParentDirectoryURLKey          NS_AVAILABLE(10_6, 4_0); // The resource's parent directory, if any (Read-only, value type NSURL)
FOUNDATION_EXPORT NSString * const NSURLVolumeURLKey                   NS_AVAILABLE(10_6, 4_0); // URL of the volume on which the resource is stored (Read-only, value type NSURL)
FOUNDATION_EXPORT NSString * const NSURLTypeIdentifierKey              NS_AVAILABLE(10_6, 4_0); // Uniform type identifier (UTI) for the resource (Read-only, value type NSString)
FOUNDATION_EXPORT NSString * const NSURLLocalizedTypeDescriptionKey    NS_AVAILABLE(10_6, 4_0); // User-visible type or "kind" description (Read-only, value type NSString)
FOUNDATION_EXPORT NSString * const NSURLLabelNumberKey                 NS_AVAILABLE(10_6, 4_0); // The label number assigned to the resource (Read-write, value type NSNumber)
FOUNDATION_EXPORT NSString * const NSURLLabelColorKey                  NS_AVAILABLE(10_6, 4_0); // The color of the assigned label (Read-only, value type NSColor)
FOUNDATION_EXPORT NSString * const NSURLLocalizedLabelKey              NS_AVAILABLE(10_6, 4_0); // The user-visible label text (Read-only, value type NSString)
FOUNDATION_EXPORT NSString * const NSURLEffectiveIconKey               NS_AVAILABLE(10_6, 4_0); // The icon normally displayed for the resource (Read-only, value type NSImage)
FOUNDATION_EXPORT NSString * const NSURLCustomIconKey                  NS_AVAILABLE(10_6, 4_0); // The custom icon assigned to the resource, if any (Currently not implemented, value type NSImage)
FOUNDATION_EXPORT NSString * const NSURLFileResourceIdentifierKey      NS_AVAILABLE(10_7, 5_0); // An identifier which can be used to compare two file system objects for equality using -isEqual (i.e, two object identifiers are equal if they have the same file system path or if the paths are linked to same inode on the same file system). This identifier is not persistent across system restarts. (Read-only, value type id <NSCopying, NSCoding, NSObject>)
FOUNDATION_EXPORT NSString * const NSURLVolumeIdentifierKey            NS_AVAILABLE(10_7, 5_0); // An identifier that can be used to identify the volume the file system object is on. Other objects on the same volume will have the same volume identifier and can be compared using for equality using -isEqual. This identifier is not persistent across system restarts. (Read-only, value type id <NSCopying, NSCoding, NSObject>)
FOUNDATION_EXPORT NSString * const NSURLPreferredIOBlockSizeKey        NS_AVAILABLE(10_7, 5_0); // The optimal block size when reading or writing this file's data, or nil if not available. (Read-only, value type NSNumber)
FOUNDATION_EXPORT NSString * const NSURLIsReadableKey                  NS_AVAILABLE(10_7, 5_0); // true if this process (as determined by EUID) can read the resource. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLIsWritableKey                  NS_AVAILABLE(10_7, 5_0); // true if this process (as determined by EUID) can write to the resource. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLIsExecutableKey                NS_AVAILABLE(10_7, 5_0); // true if this process (as determined by EUID) can execute a file resource or search a directory resource. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLFileSecurityKey                NS_AVAILABLE(10_7, 5_0); // The file system object's security information encapsulated in a NSFileSecurity object. (Read-write, Value type NSFileSecurity)
FOUNDATION_EXPORT NSString * const NSURLIsExcludedFromBackupKey        NS_AVAILABLE(10_8, 5_1); // true if resource should be excluded from backups, false otherwise (Read-write, value type boolean NSNumber). This property is only useful for excluding cache and other application support files which are not needed in a backup. Some operations commonly made to user documents will cause this property to be reset to false and so this property should not be used on user documents.
FOUNDATION_EXPORT NSString * const NSURLTagNamesKey                    NS_AVAILABLE(10_9, NA);	// The array of Tag names (Read-write, value type NSArray of NSString)
FOUNDATION_EXPORT NSString * const NSURLPathKey                        NS_AVAILABLE(10_8, 6_0); // the URL's path as a file system path (Read-only, value type NSString)
FOUNDATION_EXPORT NSString * const NSURLIsMountTriggerKey              NS_AVAILABLE(10_7, 5_0); // true if this URL is a file system trigger directory. Traversing or opening a file system trigger will cause an attempt to mount a file system on the trigger directory. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLGenerationIdentifierKey NS_AVAILABLE(10_10, 8_0); // An opaque generation identifier which can be compared using isEqual: to determine if the data in a document has been modified. For URLs which refer to the same file inode, the generation identifier will change when the data in the file's data fork is changed (changes to extended attributes or other file system metadata do not change the generation identifier). For URLs which refer to the same directory inode, the generation identifier will change when direct children of that directory are added, removed or renamed (changes to the data of the direct children of that directory will not change the generation identifier). The generation identifier is persistent across system restarts. The generation identifier is tied to a specific document on a specific volume and is not transferred when the document is copied to another volume. This property is not supported by all volumes. (Read-only, value type id <NSCopying, NSCoding, NSObject>
FOUNDATION_EXPORT NSString * const NSURLDocumentIdentifierKey NS_AVAILABLE(10_10, 8_0); // The document identifier -- a value assigned by the kernel to a document (which can be either a file or directory) and is used to identify the document regardless of where it gets moved on a volume. The document identifier survives "safe save” operations; i.e it is sticky to the path it was assigned to (-replaceItemAtURL:withItemAtURL:backupItemName:options:resultingItemURL:error: is the preferred safe-save API). The document identifier is persistent across system restarts. The document identifier is not transferred when the file is copied. Document identifiers are only unique within a single volume. This property is not supported by all volumes. (Read-only, value type NSNumber)
FOUNDATION_EXPORT NSString * const NSURLAddedToDirectoryDateKey NS_AVAILABLE(10_10, 8_0); // The date the resource was created, or renamed into or within its parent directory. Note that inconsistent behavior may be observed when this attribute is requested on hard-linked items. This property is not supported by all volumes. (Read-only, value type NSDate)
FOUNDATION_EXPORT NSString * const NSURLQuarantinePropertiesKey NS_AVAILABLE(10_10, NA); // The quarantine properties as defined in LSQuarantine.h. To remove quarantine information from a file, pass NSNull as the value when setting this property. (Read-write, value type NSDictionary)
FOUNDATION_EXPORT NSString * const NSURLFileResourceTypeKey            NS_AVAILABLE(10_7, 5_0); // Returns the file system object type. (Read-only, value type NSString)

/* The file system object type values returned for the NSURLFileResourceTypeKey
 */
FOUNDATION_EXPORT NSString * const NSURLFileResourceTypeNamedPipe      NS_AVAILABLE(10_7, 5_0);
FOUNDATION_EXPORT NSString * const NSURLFileResourceTypeCharacterSpecial NS_AVAILABLE(10_7, 5_0);
FOUNDATION_EXPORT NSString * const NSURLFileResourceTypeDirectory      NS_AVAILABLE(10_7, 5_0);
FOUNDATION_EXPORT NSString * const NSURLFileResourceTypeBlockSpecial   NS_AVAILABLE(10_7, 5_0);
FOUNDATION_EXPORT NSString * const NSURLFileResourceTypeRegular        NS_AVAILABLE(10_7, 5_0);
FOUNDATION_EXPORT NSString * const NSURLFileResourceTypeSymbolicLink   NS_AVAILABLE(10_7, 5_0);
FOUNDATION_EXPORT NSString * const NSURLFileResourceTypeSocket         NS_AVAILABLE(10_7, 5_0);
FOUNDATION_EXPORT NSString * const NSURLFileResourceTypeUnknown        NS_AVAILABLE(10_7, 5_0);

FOUNDATION_EXPORT NSString * const NSURLThumbnailDictionaryKey         NS_AVAILABLE(10_10, 8_0); // dictionary of NSImage/UIImage objects keyed by size
FOUNDATION_EXPORT NSString * const NSURLThumbnailKey                   NS_AVAILABLE_MAC(10_10); // returns all thumbnails as a single NSImage

/* size keys for the dictionary returned by NSURLThumbnailDictionaryKey
 */
FOUNDATION_EXPORT NSString * const NSThumbnail1024x1024SizeKey         NS_AVAILABLE(10_10, 8_0); // size key for a 1024 x 1024 thumbnail image

/* Resource keys applicable only to regular files
 */
FOUNDATION_EXPORT NSString * const NSURLFileSizeKey                    NS_AVAILABLE(10_6, 4_0); // Total file size in bytes (Read-only, value type NSNumber)
FOUNDATION_EXPORT NSString * const NSURLFileAllocatedSizeKey           NS_AVAILABLE(10_6, 4_0); // Total size allocated on disk for the file in bytes (number of blocks times block size) (Read-only, value type NSNumber)
FOUNDATION_EXPORT NSString * const NSURLTotalFileSizeKey               NS_AVAILABLE(10_7, 5_0); // Total displayable size of the file in bytes (this may include space used by metadata), or nil if not available. (Read-only, value type NSNumber)
FOUNDATION_EXPORT NSString * const NSURLTotalFileAllocatedSizeKey      NS_AVAILABLE(10_7, 5_0); // Total allocated size of the file in bytes (this may include space used by metadata), or nil if not available. This can be less than the value returned by NSURLTotalFileSizeKey if the resource is compressed. (Read-only, value type NSNumber)
FOUNDATION_EXPORT NSString * const NSURLIsAliasFileKey		   NS_AVAILABLE(10_6, 4_0); // true if the resource is a Finder alias file or a symlink, false otherwise ( Read-only, value type boolean NSNumber)

/* Volumes resource keys 
 
 As a convenience, volume resource values can be requested from any file system URL. The value returned will reflect the property value for the volume on which the resource is located.
 */
FOUNDATION_EXPORT NSString * const NSURLVolumeLocalizedFormatDescriptionKey NS_AVAILABLE(10_6, 4_0); // The user-visible volume format (Read-only, value type NSString)
FOUNDATION_EXPORT NSString * const NSURLVolumeTotalCapacityKey              NS_AVAILABLE(10_6, 4_0); // Total volume capacity in bytes (Read-only, value type NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeAvailableCapacityKey          NS_AVAILABLE(10_6, 4_0); // Total free space in bytes (Read-only, value type NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeResourceCountKey              NS_AVAILABLE(10_6, 4_0); // Total number of resources on the volume (Read-only, value type NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeSupportsPersistentIDsKey      NS_AVAILABLE(10_6, 4_0); // true if the volume format supports persistent object identifiers and can look up file system objects by their IDs (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeSupportsSymbolicLinksKey      NS_AVAILABLE(10_6, 4_0); // true if the volume format supports symbolic links (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeSupportsHardLinksKey          NS_AVAILABLE(10_6, 4_0); // true if the volume format supports hard links (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeSupportsJournalingKey         NS_AVAILABLE(10_6, 4_0); // true if the volume format supports a journal used to speed recovery in case of unplanned restart (such as a power outage or crash). This does not necessarily mean the volume is actively using a journal. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeIsJournalingKey               NS_AVAILABLE(10_6, 4_0); // true if the volume is currently using a journal for speedy recovery after an unplanned restart. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeSupportsSparseFilesKey        NS_AVAILABLE(10_6, 4_0); // true if the volume format supports sparse files, that is, files which can have 'holes' that have never been written to, and thus do not consume space on disk. A sparse file may have an allocated size on disk that is less than its logical length (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeSupportsZeroRunsKey           NS_AVAILABLE(10_6, 4_0); // For security reasons, parts of a file (runs) that have never been written to must appear to contain zeroes. true if the volume keeps track of allocated but unwritten runs of a file so that it can substitute zeroes without actually writing zeroes to the media. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeSupportsCaseSensitiveNamesKey NS_AVAILABLE(10_6, 4_0); // true if the volume format treats upper and lower case characters in file and directory names as different. Otherwise an upper case character is equivalent to a lower case character, and you can't have two names that differ solely in the case of the characters. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeSupportsCasePreservedNamesKey NS_AVAILABLE(10_6, 4_0); // true if the volume format preserves the case of file and directory names.  Otherwise the volume may change the case of some characters (typically making them all upper or all lower case). (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeSupportsRootDirectoryDatesKey NS_AVAILABLE(10_7, 5_0); // true if the volume supports reliable storage of times for the root directory. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeSupportsVolumeSizesKey        NS_AVAILABLE(10_7, 5_0); // true if the volume supports returning volume size values (NSURLVolumeTotalCapacityKey and NSURLVolumeAvailableCapacityKey). (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeSupportsRenamingKey           NS_AVAILABLE(10_7, 5_0); // true if the volume can be renamed. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeSupportsAdvisoryFileLockingKey NS_AVAILABLE(10_7, 5_0); // true if the volume implements whole-file flock(2) style advisory locks, and the O_EXLOCK and O_SHLOCK flags of the open(2) call. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeSupportsExtendedSecurityKey   NS_AVAILABLE(10_7, 5_0); // true if the volume implements extended security (ACLs). (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeIsBrowsableKey                NS_AVAILABLE(10_7, 5_0); // true if the volume should be visible via the GUI (i.e., appear on the Desktop as a separate volume). (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeMaximumFileSizeKey            NS_AVAILABLE(10_7, 5_0); // The largest file size (in bytes) supported by this file system, or nil if this cannot be determined. (Read-only, value type NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeIsEjectableKey                NS_AVAILABLE(10_7, 5_0); // true if the volume's media is ejectable from the drive mechanism under software control. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeIsRemovableKey                NS_AVAILABLE(10_7, 5_0); // true if the volume's media is removable from the drive mechanism. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeIsInternalKey                 NS_AVAILABLE(10_7, 5_0); // true if the volume's device is connected to an internal bus, false if connected to an external bus, or nil if not available. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeIsAutomountedKey              NS_AVAILABLE(10_7, 5_0); // true if the volume is automounted. Note: do not mistake this with the functionality provided by kCFURLVolumeSupportsBrowsingKey. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeIsLocalKey                    NS_AVAILABLE(10_7, 5_0); // true if the volume is stored on a local device. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeIsReadOnlyKey                 NS_AVAILABLE(10_7, 5_0); // true if the volume is read-only. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLVolumeCreationDateKey               NS_AVAILABLE(10_7, 5_0); // The volume's creation date, or nil if this cannot be determined. (Read-only, value type NSDate)
FOUNDATION_EXPORT NSString * const NSURLVolumeURLForRemountingKey           NS_AVAILABLE(10_7, 5_0); // The NSURL needed to remount a network volume, or nil if not available. (Read-only, value type NSURL)
FOUNDATION_EXPORT NSString * const NSURLVolumeUUIDStringKey                 NS_AVAILABLE(10_7, 5_0); // The volume's persistent UUID as a string, or nil if a persistent UUID is not available for the volume. (Read-only, value type NSString)
FOUNDATION_EXPORT NSString * const NSURLVolumeNameKey                       NS_AVAILABLE(10_7, 5_0); // The name of the volume (Read-write if NSURLVolumeSupportsRenamingKey is YES, otherwise read-only, value type NSString)
FOUNDATION_EXPORT NSString * const NSURLVolumeLocalizedNameKey              NS_AVAILABLE(10_7, 5_0); // The user-presentable name of the volume (Read-only, value type NSString)

/* Ubiquitous item resource keys
 */
FOUNDATION_EXPORT NSString * const NSURLIsUbiquitousItemKey                     NS_AVAILABLE(10_7, 5_0); // true if this item is synced to the cloud, false if it is only a local file. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLUbiquitousItemHasUnresolvedConflictsKey NS_AVAILABLE(10_7, 5_0); // true if this item has conflicts outstanding. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLUbiquitousItemIsDownloadedKey           NS_DEPRECATED(10_7, 10_9, 5_0, 7_0, "Use NSURLUbiquitousItemDownloadingStatusKey instead"); // equivalent to NSURLUbiquitousItemDownloadingStatusKey == NSURLUbiquitousItemDownloadingStatusCurrent. Has never behaved as documented in earlier releases, hence deprecated.  (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLUbiquitousItemIsDownloadingKey          NS_AVAILABLE(10_7, 5_0); // true if data is being downloaded for this item. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLUbiquitousItemIsUploadedKey             NS_AVAILABLE(10_7, 5_0); // true if there is data present in the cloud for this item. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLUbiquitousItemIsUploadingKey            NS_AVAILABLE(10_7, 5_0); // true if data is being uploaded for this item. (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLUbiquitousItemPercentDownloadedKey      NS_DEPRECATED(10_7, 10_8, 5_0, 6_0); // Use NSMetadataQuery and NSMetadataUbiquitousItemPercentDownloadedKey on NSMetadataItem instead
FOUNDATION_EXPORT NSString * const NSURLUbiquitousItemPercentUploadedKey        NS_DEPRECATED(10_7, 10_8, 5_0, 6_0); // Use NSMetadataQuery and NSMetadataUbiquitousItemPercentUploadedKey on NSMetadataItem instead
FOUNDATION_EXPORT NSString * const NSURLUbiquitousItemDownloadingStatusKey      NS_AVAILABLE(10_9, 7_0); // returns the download status of this item. (Read-only, value type NSString). Possible values below.
FOUNDATION_EXPORT NSString * const NSURLUbiquitousItemDownloadingErrorKey       NS_AVAILABLE(10_9, 7_0); // returns the error when downloading the item from iCloud failed, see the NSUbiquitousFile section in FoundationErrors.h (Read-only, value type NSError)
FOUNDATION_EXPORT NSString * const NSURLUbiquitousItemUploadingErrorKey         NS_AVAILABLE(10_9, 7_0); // returns the error when uploading the item to iCloud failed, see the NSUbiquitousFile section in FoundationErrors.h (Read-only, value type NSError)
FOUNDATION_EXPORT NSString * const NSURLUbiquitousItemDownloadRequestedKey      NS_AVAILABLE(10_10, 8_0); // returns whether a download of this item has already been requested with an API like -startDownloadingUbiquitousItemAtURL:error: (Read-only, value type boolean NSNumber)
FOUNDATION_EXPORT NSString * const NSURLUbiquitousItemContainerDisplayNameKey   NS_AVAILABLE(10_10, 8_0); // returns the name of this item's container as displayed to users.


/* The values returned for the NSURLUbiquitousItemDownloadingStatusKey
 */
FOUNDATION_EXPORT NSString * const NSURLUbiquitousItemDownloadingStatusNotDownloaded  NS_AVAILABLE(10_9, 7_0); // this item has not been downloaded yet. Use startDownloadingUbiquitousItemAtURL:error: to download it.
FOUNDATION_EXPORT NSString * const NSURLUbiquitousItemDownloadingStatusDownloaded     NS_AVAILABLE(10_9, 7_0); // there is a local version of this item available. The most current version will get downloaded as soon as possible.
FOUNDATION_EXPORT NSString * const NSURLUbiquitousItemDownloadingStatusCurrent        NS_AVAILABLE(10_9, 7_0); // there is a local version of this item and it is the most up-to-date version known to this device.
