#!/bin/bash

#
# Helper functions.
#

function gds_continue() {
	echo ""
	echo -n "Do you want to continue? (Y/N) "

	read TMP_CONTINUE_YN

	if [ "$TMP_CONTINUE_YN" != "Y" ] ; then
	        echo "$0: Not continuing."
        	exit 1
	fi
}

function gds_sysexit() {
	echo $1
	exit 1
}

function gds_log() {
	TIMESTAMP=`date +"%d-%m-%Y %H:%M:%S"`
	echo "$2"
	echo "[ $TIMESTAMP ]  $2" >> $1
}

function gds_clone() {
	$GDS_GIT_PATH clone "git@github.com:$1/$2.git" || gds_sysexit "Unable to clone repository $1/$2 from GitHub"
}

function gds_checkout_branch() {
	$GDS_GIT_PATH checkout "$1"
}

function gds_new_branch() {
	$GDS_GIT_PATH checkout -b "$1" || gds_sysexit "Unable to perform git checkout branch $1"
}

#
# Command line parameters
#

GDS_HELPER_SCRIPT_FOLDER_PATH="$1"
GDS_WORKSPACE="$2"
GDS_GH_REPO_OWNER="$3"
GDS_GH_REPO_NAMES="$4"
GDS_GH_ACCESS_TOKEN="$5"
GDS_BRANCHES="$6"

if [ "$GDS_HELPER_SCRIPT_FOLDER_PATH" == "" ] || [ "$GDS_WORKSPACE" == "" ] || [ "$GDS_GH_REPO_OWNER" == "" ] || [ "$GDS_GH_REPO_NAMES" == "" ] || [ "$GDS_GH_ACCESS_TOKEN" == "" ] || [ "$GDS_BRANCHES" == "" ] ; then
	echo "$0: Invalid usage."
	echo "Usage: $0 path-to-helper-scripts workspace-directory github-repo-owner github-repo-names github-access-token git-branches"
	exit 1
fi

GDS_GH_REPO_NAMES=`echo $GDS_GH_REPO_NAMES | sed 's/,/ /g'`
GDS_BRANCHES=`echo $GDS_BRANCHES | sed 's/,/ /g'`

GDS_PR_LOG_FILE="$GDS_WORKSPACE/pull-request-log-file.log"

GDS_REPOS_LEFT=""

#
# Check for tools
#

GDS_PHP_PATH=`whereis -b php | awk '{print $2}'`
GDS_GIT_PATH=`whereis -b git | awk '{print $2}'`
GDS_CURL_PATH=`whereis -b curl | awk '{print $2}'`
GDS_MKTEMP_PATH=`whereis -b mktemp  | awk '{print $2}'`

if [ "$GDS_PHP_PATH" == "" ] ; then
	gds_sysexit "Missing PHP"	
fi

if [ "$GDS_GIT_PATH" == "" ] ; then
	gds_sysexit "Missing git tool"
fi

if [ "$GDS_CURL_PATH" == "" ] ; then
	gds_sysexit "Missing curl tool"
fi

if [ "$GDS_MKTEMP_PATH" == "" ] ; then
	gds_sysexit "Missing mktemp tool"
fi


#
# Display a startup screen
#

echo "============================================================================================"
echo "                                      $0"
echo "============================================================================================"
echo "                                         Instructions:"
echo ""
echo "   Will attempt to replace invocations to the Gutenberg Ramp plugin with WordPress Core"
echo "   filters in every file in each repository specified, and in each branch specified as"
echo "   well. Will create a Pull-Request automatically to push the changes to GitHub if the"
echo "   changes are approved by the user." 
echo ""
echo "   For example:"
echo "        'gutenberg_ramp_load_gutenberg( [ 'load' => 0, ] );'"
echo ""
echo "   will be replaced by:"
echo "        'add_filter( 'use_block_editor_for_post', '__return_false' );'",
echo ""
echo "   And:"
echo "        'gutenberg_ramp_load_gutenberg( [ 'load' => true, ] );'"
echo ""
echo "   will be replaced by:"
echo "        'add_filter( 'use_block_editor_for_post', '__return_true' );'",
echo ""
echo "============================================================================================"
echo "                                        Run-time parameters:"
echo ""
echo "GitHub Repository owner: $GDS_GH_REPO_OWNER"
echo "GitHub Repositories: $GDS_GH_REPO_NAMES"
echo "GitHub Access Token: ***"
echo ""
echo "Git branches to process: $GDS_BRANCHES"
echo ""
echo "Path to helper scripts: $GDS_HELPER_SCRIPT_FOLDER_PATH"
echo "Path to workspace directory: $GDS_WORKSPACE"
echo "Path to log file for Pull-Requests: $GDS_PR_LOG_FILE"
echo ""
echo "Path to PHP: $GDS_PHP_PATH"
echo "Path to git: $GDS_GIT_PATH"
echo "Path to curl: $GDS_CURL_PATH"
echo "Path to mktemp: $GDS_MKTEMP_PATH"
echo ""
echo "============================================================================================"


gds_continue


for GDS_REPO_NAME in $(echo "$GDS_GH_REPO_NAMES") ; do

	GDS_TEMP_REPO_DIR=`$GDS_MKTEMP_PATH -d "$GDS_WORKSPACE/$GDS_REPO_NAME-XXXXXXXXX"`
	GDS_TEMP_REPO_LOG="$GDS_WORKSPACE/$GDS_REPO_NAME.log"

	pushd $GDS_TEMP_REPO_DIR || gds_sysexit "Unable to enter temporary directory"

	gds_log $GDS_TEMP_REPO_LOG "Cloning repository $GDS_GH_REPO_OWNER/$GDS_REPO_NAME into $GDS_TEMP_REPO_DIR"
	gds_clone "$GDS_GH_REPO_OWNER" "$GDS_REPO_NAME"

	popd

	for GDS_CHECKOUT_BRANCH_NAME in $(echo "$GDS_BRANCHES") ; do

		TIMESTAMP=`date +%s`
		GDS_NEW_BRANCH_NAME="gutenberg-ramp-removal-$GDS_CHECKOUT_BRANCH_NAME-$TIMESTAMP"

		GDS_TEMP_REPO_DIFF="$GDS_TEMP_REPO_DIR-$GDS_CHECKOUT_BRANCH_NAME.diff"

		gds_log $GDS_TEMP_REPO_LOG "Processing $GDS_GH_REPO_OWNER/$GDS_REPO_NAME, branch $GDS_CHECKOUT_BRANCH_NAME"

		pushd $GDS_TEMP_REPO_DIR || gds_sysexit "Unable to enter temporary directory"

		pushd $GDS_REPO_NAME || gds_sysexit "Unable to change to/from temporary repository directory, $GDS_TEMP_REPO_DIR"

		gds_log $GDS_TEMP_REPO_LOG "Checking out branch $GDS_CHECKOUT_BRANCH_NAME"
		gds_checkout_branch "$GDS_CHECKOUT_BRANCH_NAME" 

		if [ "$?" != "0" ] ; then
			gds_log $GDS_TEMP_REPO_LOG "Unable to perform git checkout branch $GDS_CHECKOUT_BRANCH_NAME"
			continue
		fi
		
		gds_log $GDS_TEMP_REPO_LOG "Updating branch..."
		$GDS_GIT_PATH pull

		gds_log $GDS_TEMP_REPO_LOG "Checking out new branch $GDS_NEW_BRANCH_NAME"
		gds_new_branch "$GDS_NEW_BRANCH_NAME"
	
		GDS_PROCESS_FILES=`find "." -name '*.php' -type f`
		GDS_PROCESSED_FILES_CHANGED=""
		GDS_CALLS_REMAINING_FILES=""

		for GDS_FILE_NAME in $(echo "$GDS_PROCESS_FILES") ; do
			gds_log $GDS_TEMP_REPO_LOG "Processing file $GDS_FILE_NAME"

			GDS_ORG_FILE_MD5=`md5sum $GDS_FILE_NAME`
			GDS_ORG_FILE_PERMISSIONS=`stat -c "%a" $GDS_FILE_NAME`

			GDS_TEMP_FILE=`$GDS_MKTEMP_PATH /tmp/gds-XXXXXXXXX.php`
		
			$GDS_HELPER_SCRIPT_FOLDER_PATH/gutenberg-deprecation-helper.php other < $GDS_FILE_NAME \
			| $GDS_HELPER_SCRIPT_FOLDER_PATH/gutenberg-deprecation-helper.php function_exists \
			> $GDS_TEMP_FILE

			TEMP_NOW_MD5=`md5sum $GDS_TEMP_FILE`

			if [ "$GDS_ORG_FILE_MD5" != "$TEMP_NOW_MD5" ] ; then
				mv -f $GDS_TEMP_FILE $GDS_FILE_NAME

				chmod $GDS_ORG_FILE_PERMISSIONS $GDS_FILE_NAME

				GDS_PROCESSED_FILES_CHANGED="$GDS_PROCESSED_FILES_CHANGED $GDS_FILE_NAME"

			else
				rm -f "$GDS_TEMP_FILE"
			fi

			GDS_TEMP_GR_CALLS=`grep gutenberg_ramp_load_gutenberg $GDS_FILE_NAME | wc -l`

			if [ "$GDS_TEMP_GR_CALLS" != "0" ] ; then
				GDS_CALLS_REMAINING_FILES="$GDS_CALLS_REMAINING_FILES $GDS_FILE_NAME"
			fi

		done

		gds_log $GDS_TEMP_REPO_LOG "Performing \"git diff\"... see $GDS_TEMP_REPO_DIFF"
		$GDS_GIT_PATH diff $GDS_PROCESSED_FILES_CHANGED > $GDS_TEMP_REPO_DIFF
		$GDS_GIT_PATH diff $GDS_PROCESSED_FILES_CHANGED

		echo "Files with Gutenberg Ramp calls remaining: $GDS_CALLS_REMAINING_FILES"
		echo ""

		echo ""
		echo "Should changes be committed to the repository and a Pull-Request created?"
		echo "If you select Y, this will be performed, but the temporary repository"
		echo "left in place. Otherwise the repository will be left in place for "
		echo "further inspection and work."
		echo ""
		echo -n "Do you want to continue? (Y/N) "

		read TMP_CONTINUE_YN


		if [ "$TMP_CONTINUE_YN" != "Y" ] ; then
			gds_log $GDS_TEMP_REPO_LOG "Not committing nor opening Pull-Request."
			gds_log $GDS_TEMP_REPO_LOG "Repository left in place at: $GDS_TEMP_REPO_DIR"
			gds_log $GDS_TEMP_REPO_LOG "Resetting repository..."

			$GDS_GIT_PATH checkout . | tee -a $GDS_TEMP_REPO_LOG

			GDS_REPOS_LEFT="$GDS_REPOS_LEFT $GDS_REPO_NAME"

			popd && popd

		elif [ "$TMP_CONTINUE_YN" == "Y" ] ; then
			gds_log $GDS_TEMP_REPO_LOG "Committing changes and creating Pull-Request..."

			gds_log $GDS_TEMP_REPO_LOG "Calculating if any code was altered..."

			GDS_ALTERED_LINES_CNT=`$GDS_GIT_PATH diff | wc -l`

			# Check if there is anything to commit
			# and don't do anything if nothing was changed
			if [ "$GDS_ALTERED_LINES_CNT" == "0" ] ; then
				gds_log $GDS_TEMP_REPO_LOG "Not committing, no code was altered"

			elif [ "$GDS_ALTERED_LINES_CNT" -gt "0" ] ; then

				gds_log $GDS_TEMP_REPO_LOG "Committing..." && \
				$GDS_GIT_PATH commit $GDS_PROCESSED_FILES_CHANGED | tee -a $GDS_TEMP_REPO_LOG && \
				gds_log $GDS_TEMP_REPO_LOG "Pushing..." && \
				$GDS_GIT_PATH push --set-upstream "origin" $GDS_NEW_BRANCH_NAME | tee -a $GDS_TEMP_REPO_LOG && \

				GDS_GH_PR_TITLE="Deprecation of Gutenberg Ramp (branch: $GDS_CHECKOUT_BRANCH_NAME)"
				GDS_GH_PR_BODY="This PR is needed in order to remove any invocations to Gutenberg Ramp."

				GDS_CALLS_REMAINING_FILES_HTML=`echo $GDS_CALLS_REMAINING_FILES | sed 's/\.\//%LINEBREAK%\* /g'`

				GDS_GH_PR_BODY="$GDS_GH_PR_BODY <br><br>Note that there are a number of files that still will need to be inspected manually, as these contain references to Gutenberg Ramp: $GDS_CALLS_REMAINING_FILES_HTML"

				GDS_GH_PR_NUMBER=`$GDS_HELPER_SCRIPT_FOLDER_PATH/json-helper.php \
					"pr-create" \
					"https://api.github.com/repos/$GDS_GH_REPO_OWNER/$GDS_REPO_NAME/pulls" \
					"$GDS_GH_ACCESS_TOKEN" \
					"{\"draft\":true,\"head\":\"$GDS_NEW_BRANCH_NAME\",\"base\":\"$GDS_CHECKOUT_BRANCH_NAME\",\"title\":\"$GDS_GH_PR_TITLE\",\"body\":\"$GDS_GH_PR_BODY\"}" \
					| tee -a $GDS_TEMP_REPO_LOG`

				GDS_GH_PR_URL="https://github.com/$GDS_GH_REPO_OWNER/$GDS_REPO_NAME/pull/$GDS_GH_PR_NUMBER"

				gds_log $GDS_TEMP_REPO_LOG "URL to Pull-Request: $GDS_GH_PR_URL"
				gds_log $GDS_PR_LOG_FILE "Pull-Request for $GDS_GH_REPO_OWNER/$GDS_REPO_NAME (branch: $GDS_CHECKOUT_BRANCH_NAME): $GDS_GH_PR_URL"

		
				$GDS_HELPER_SCRIPT_FOLDER_PATH/json-helper.php \
					"pr-add-label" \
					"https://api.github.com/repos/$GDS_GH_REPO_OWNER/$GDS_REPO_NAME/issues/$GDS_GH_PR_NUMBER/labels" \
					"$GDS_GH_ACCESS_TOKEN" \
					"{\"labels\":[\"gutenberg-ramp-deprecation\"]}" \
					| tee -a $GDS_TEMP_REPO_LOG
			fi

			gds_log $GDS_TEMP_REPO_LOG "Repository left in place at: $GDS_TEMP_REPO_DIR"

			popd && popd

		fi

		gds_log $GDS_TEMP_REPO_LOG "Finished processing $GDS_GH_REPO_OWNER/$GDS_REPO_NAME"
		gds_log $GDS_TEMP_REPO_LOG "***"
	done

	gds_log $GDS_TEMP_REPO_LOG "Removing $GDS_TEMP_REPO_DIR"
done

echo "Finished processing all repositories"
echo "Repositories left in place: $GDS_REPOS_LEFT"
