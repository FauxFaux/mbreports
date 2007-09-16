-- BF's craziness:

select 
artist.id as artist_id, album.id as album_id, album.name as album_name, albumjoin.sequence as track_number, track.name as track_name, track.id as track_id
from 
artist,track,album,albumjoin
where
track.id = albumjoin.track AND
albumjoin.album = album.id AND
album.artist = artist.id AND
artist.name = 'Ennio Morricone'
order by album.name,track_number


-- String_accum:

CREATE FUNCTION concat(text, text) RETURNS text AS 'select $1||$2' LANGUAGE SQL IMMUTABLE RETURNS NULL ON NULL INPUT;
CREATE AGGREGATE string_accum (text) ( sfunc = concat, stype = text, initcond = '' );


create or replace temporary view at_noempties as select * from album_tracklist where sum_array(tracklist) != 0;
--select track_count,count(*) from at_noempties group by track_count order by count desc;
select * from at_noempties where track_count = 20 order by tracklist[1] asc;


CREATE OR REPLACE FUNCTION array_search(arr integer[], what integer)
  RETURNS boolean AS
$BODY$
	BEGIN
		FOR i IN ARRAY_LOWER(arr,1)..ARRAY_UPPER(arr,1) LOOP
			IF arr[i] =  what THEN
				RETURN true;
			END IF;
		END LOOP;
		RETURN false;
	END
$BODY$
  LANGUAGE 'plpgsql' VOLATILE;
