#
# DO NOT EDIT THIS FILE MANUALLY
#
class BeachCli < Formula
  desc "Command line tool for Flownative Beach"
  homepage "https://www.flownative.com/beach"
  url "https://storage.googleapis.com/cli-tool.beach.flownative.cloud/beach-${APP_VERSION}.phar"
  sha256 "${SHA256_HASH}"

  bottle :unneeded

  def install
    bin.install "beach-${APP_VERSION}.phar" => "beach"
  end
end